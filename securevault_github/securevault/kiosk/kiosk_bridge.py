"""
SecureVault - kiosk fingerprint bridge (run on the laptop the Arduino is plugged into)

  Server (PHP)  <--HTTP-->  kiosk_bridge.py  <--USB serial-->  Arduino + AS608

Loop:
  1. GET  fingerprint_device_poll.php   -> is there an enroll/verify job?
  2. ENROLL: send "ENROLL" to Arduino, forward progress to
             fingerprint_enroll_step.php, then POST the template to
             fingerprint_enroll_report.php
     VERIFY: send "VERIFY <template hex>" to Arduino, POST MATCH/NOMATCH
             to fingerprint_scan_report.php

Install:  pip install pyserial
Run:      python kiosk_bridge.py
"""
import json
import sys
import time
import urllib.error
import urllib.request

import serial

# ───────────────────────── CONFIG ─────────────────────────
# Folder on the server that holds the fingerprint_*.php files.
#   Local XAMPP test : "http://localhost/securevault/api"
#   Pi via tunnel    : "https://app.securevault.com.my/api"
API_BASE = "http://localhost/securevault/api"

# Must be identical to FP_DEVICE_KEY on the server.
DEVICE_KEY = "CHANGE_ME_DEVICE_KEY"

# "COM4" / "/dev/ttyUSB0" when the Arduino is plugged into this machine, or
# "socket://127.0.0.1:7000" when it is on the laptop (kiosk_forwarder.py + SSH tunnel).
SERIAL_PORT = "COM4"
SERIAL_BAUD = 115200

POLL_INTERVAL = 1.0     # seconds between job polls
JOB_TIMEOUT = 55        # seconds to wait for the Arduino to finish one job
HEARTBEAT_INTERVAL = 10 # seconds between idle PING checks of the Arduino link
# ──────────────────────────────────────────────────────────


def log(msg):
    print(time.strftime("[%H:%M:%S] ") + msg, flush=True)


def api(path, body=None):
    """GET (body None) or POST JSON to the server. Returns parsed JSON or None."""
    url = f"{API_BASE}/{path}"
    data = None if body is None else json.dumps(body).encode()
    req = urllib.request.Request(url, data=data, method="GET" if body is None else "POST")
    req.add_header("X-Device-Key", DEVICE_KEY)
    req.add_header("User-Agent", "SecureVault-Kiosk/1.0")
    if body is not None:
        req.add_header("Content-Type", "application/json")
    try:
        with urllib.request.urlopen(req, timeout=10) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        if e.code == 401:
            log("Server rejected the device key (401) - check DEVICE_KEY")
        else:
            log(f"Server error {e.code} on {path}")
    except (urllib.error.URLError, TimeoutError, json.JSONDecodeError) as e:
        log(f"Cannot reach server ({path}): {e}")
    return None


class Arduino:
    def __init__(self):
        self.ser = None

    def connect(self):
        while True:
            try:
                # serial_for_url accepts a normal port ("COM4", "/dev/ttyUSB0")
                # or a forwarded one ("socket://127.0.0.1:7000").
                self.ser = serial.serial_for_url(SERIAL_PORT, SERIAL_BAUD, timeout=1)
                # Opening the port resets the Arduino; wait for READY.
                deadline = time.time() + 8
                while time.time() < deadline:
                    line = self.readline()
                    if line == "READY":
                        log(f"Arduino ready on {SERIAL_PORT}")
                        return
                    if line.startswith("ERROR"):
                        log(f"Arduino: {line}")
                # Maybe it was already running - try PING.
                self.send("PING")
                if self.readline() == "PONG":
                    log(f"Arduino ready on {SERIAL_PORT}")
                    return
                log("Arduino did not answer, retrying...")
                self.ser.close()
            except serial.SerialException as e:
                log(f"Cannot open {SERIAL_PORT}: {e} (close Serial Monitor / other scripts)")
            time.sleep(3)

    def send(self, line):
        self.ser.reset_input_buffer()
        self.ser.write((line + "\n").encode())
        self.ser.flush()

    def readline(self):
        return self.ser.readline().decode(errors="ignore").strip()

    def results(self, timeout=JOB_TIMEOUT):
        """Yield every meaningful line from the Arduino until timeout."""
        deadline = time.time() + timeout
        while time.time() < deadline:
            line = self.readline()
            if not line or line.startswith("[+]"):   # [+] = FPM library debug log
                continue
            yield line


ENROLL_STEPS = {
    "STATUS PLACE_FINGER": "waiting_first_scan",
    "STATUS REMOVE_FINGER": "remove_finger",
    "STATUS PLACE_AGAIN": "waiting_second_scan",
}


def run_enroll(dev, token):
    log("ENROLL job: place finger twice")
    dev.send("ENROLL")
    for line in dev.results():
        if line in ENROLL_STEPS:
            log(f"  {line}")
            api("fingerprint_enroll_step.php", {"token": token, "step": ENROLL_STEPS[line]})
        elif line.startswith("TEMPLATE "):
            tpl = line[len("TEMPLATE "):]
            log(f"  template captured ({len(tpl) // 2} bytes), sending to server")
            res = api("fingerprint_enroll_report.php", {"token": token, "ok": True, "template": tpl})
            log(f"  server: {res}")
            return
        elif line.startswith("ERROR"):
            log(f"  {line}")
            api("fingerprint_enroll_report.php", {"token": token, "ok": False, "message": line[6:]})
            return
    log("  timeout waiting for Arduino")
    api("fingerprint_enroll_report.php", {"token": token, "ok": False, "message": "TIMEOUT"})


def run_verify(dev, token, template_hex):
    log("VERIFY job: place finger")
    dev.send("VERIFY " + template_hex)
    for line in dev.results():
        if line.startswith("STATUS"):
            log(f"  {line}")
        elif line.startswith("MATCH "):
            score = int(line.split()[1])
            log(f"  MATCH (score {score})")
            api("fingerprint_scan_report.php", {"token": token, "result": "MATCH", "score": score})
            return
        elif line == "NOMATCH":
            log("  NOMATCH")
            api("fingerprint_scan_report.php", {"token": token, "result": "NOMATCH"})
            return
        elif line.startswith("ERROR"):
            log(f"  {line}")
            api("fingerprint_scan_report.php", {"token": token, "result": "ERROR", "message": line[6:]})
            return
    log("  timeout waiting for Arduino")
    api("fingerprint_scan_report.php", {"token": token, "result": "ERROR", "message": "TIMEOUT"})


def main():
    if DEVICE_KEY == "CHANGE_ME_DEVICE_KEY":
        sys.exit("Set DEVICE_KEY in kiosk_bridge.py (same value as FP_DEVICE_KEY on the server).")

    dev = Arduino()
    dev.connect()
    log(f"Polling {API_BASE} ...")
    last_ping = time.time()

    while True:
        try:
            # Heartbeat: while idle, make sure the Arduino link (or the SSH
            # tunnel to it) is still alive, so a dead link is fixed BEFORE a
            # user is standing at the sensor.
            if time.time() - last_ping > HEARTBEAT_INTERVAL:
                dev.send("PING")
                if not any(line == "PONG" for line in dev.results(timeout=3)):
                    raise serial.SerialException("no PONG from Arduino")
                last_ping = time.time()

            res = api("fingerprint_device_poll.php")
            job = (res or {}).get("job")
            if job and job.get("type") == "enroll":
                run_enroll(dev, job["token"])
                last_ping = time.time()
            elif job and job.get("type") == "verify":
                run_verify(dev, job["token"], job["template"])
                last_ping = time.time()
            else:
                time.sleep(POLL_INTERVAL)
        except serial.SerialException as e:
            log(f"Lost Arduino: {e}")
            try:
                dev.ser.close()
            except Exception:
                pass
            dev.connect()
        except KeyboardInterrupt:
            log("Stopped.")
            return


if __name__ == "__main__":
    main()
