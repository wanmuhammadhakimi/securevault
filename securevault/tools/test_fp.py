"""
SecureVault - test enroll/verify fingerprint (template disimpan dalam fail, bukan sensor)

Install:  pip install pyserial
Guna:
    python test_fp.py COM5 enroll ali.bin     # scan 2 kali, simpan template ke ali.bin
    python test_fp.py COM5 verify ali.bin     # scan, banding dengan ali.bin

Kalau enroll + verify jari sama dapat MATCH, dan jari lain dapat NOMATCH,
maka flow "template dalam database" dah berfungsi. Lepas tu tinggal tukar
fail .bin kepada kolum BLOB (encrypted) dalam MySQL.
"""
import sys
import time
import serial

BAUD = 115200


def open_port(port):
    ser = serial.Serial(port, BAUD, timeout=1)
    # Arduino reset bila port dibuka; tunggu READY
    deadline = time.time() + 6
    while time.time() < deadline:
        line = ser.readline().decode(errors="ignore").strip()
        if line == "READY":
            return ser
        if line.startswith("ERROR"):
            sys.exit(f"Arduino: {line}")
    # mungkin dah READY sebelum ini; cuba PING
    ser.write(b"PING\n")
    if ser.readline().decode(errors="ignore").strip() == "PONG":
        return ser
    sys.exit("Arduino tak respon. Semak port/baud.")


def wait_result(ser, timeout=40):
    deadline = time.time() + timeout
    while time.time() < deadline:
        line = ser.readline().decode(errors="ignore").strip()
        if not line:
            continue
        if line.startswith("[+]"):          # log dalaman library FPM, bukan keputusan
            print(f"  (log sensor: {line[3:]})")
            continue
        if line.startswith("STATUS"):
            msg = {
                "STATUS PLACE_FINGER": "Letak jari...",
                "STATUS REMOVE_FINGER": "Angkat jari.",
                "STATUS PLACE_AGAIN": "Letak jari yang SAMA sekali lagi...",
            }.get(line, line)
            print(msg)
            continue
        return line
    sys.exit("Timeout tunggu Arduino.")


def enroll(ser, path):
    ser.write(b"ENROLL\n")
    res = wait_result(ser)
    if not res.startswith("TEMPLATE "):
        sys.exit(f"Gagal: {res}")
    data = bytes.fromhex(res[len("TEMPLATE "):])
    with open(path, "wb") as f:
        f.write(data)
    print(f"OK: template {len(data)} byte disimpan ke {path}")


def verify(ser, path):
    with open(path, "rb") as f:
        data = f.read()
    if not data:
        sys.exit("Fail template kosong")
    ser.write(b"VERIFY " + data.hex().upper().encode() + b"\n")
    res = wait_result(ser)
    if res.startswith("MATCH "):
        print(f"PADAN  (score {res.split()[1]})")
    elif res == "NOMATCH":
        print("TAK PADAN")
    else:
        print(f"Gagal: {res}")


if __name__ == "__main__":
    if len(sys.argv) != 4 or sys.argv[2] not in ("enroll", "verify"):
        sys.exit(__doc__)
    port, action, path = sys.argv[1:]
    s = open_port(port)
    (enroll if action == "enroll" else verify)(s, path)
    s.close()
