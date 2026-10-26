"""
SecureVault - Intrusion Watcher (USB bridge)
----------------------------------------------
Runs on the SAME laptop as XAMPP, alongside it. Polls intrusion_alert.php
every few seconds; when it reports an active alert, sends '1' to the
Arduino over USB, which starts the buzzer + OLED alarm and keeps it going
CONTINUOUSLY. The alarm only stops when an admin clicks "Silence Alarm" on
the dashboard — that clears the alert flag on the server, this script
notices on its next poll and sends '0' to the Arduino to stop it.

SETUP (one-time):
  1. Install Python from https://www.python.org/downloads/ if you don't
     have it yet (tick "Add Python to PATH" during install).
  2. Open Command Prompt / Terminal and run:
         pip install pyserial requests
  3. Plug the Arduino into the laptop via USB, upload
     securevault_buzzer_oled_usb.ino to it using Arduino IDE.
  4. Find which COM port the Arduino is on: in Arduino IDE, look at
     Tools -> Port (e.g. "COM4" on Windows).
     Put that value into SERIAL_PORT below.
  5. Make sure XAMPP's Apache is running, and intrusion_alert.php is
     reachable at the URL in STATUS_URL below.
  6. Run this script:
         python intrusion_watcher.py
     Leave it running in the background during your demo.
"""

import time
import serial
import requests

# ---------------- CONFIG — edit these ----------------
SERIAL_PORT = "COM4"          # <-- change to your Arduino's port
BAUD_RATE = 9600              # must match Serial.begin() in the .ino sketch

STATUS_URL = "http://localhost/securevault/api/intrusion_alert.php?action=status"
ACK_URL = "http://localhost/securevault/api/intrusion_alert.php?action=ack"

POLL_INTERVAL_SECONDS = 2
# -------------------------------------------------------


def main():
    print(f"Connecting to Arduino on {SERIAL_PORT}...")
    try:
        arduino = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=2)
    except serial.SerialException as e:
        print(f"Could not open {SERIAL_PORT}: {e}")
        print("Check the port name in Arduino IDE (Tools -> Port) and update SERIAL_PORT above.")
        return

    time.sleep(2)  # give the Arduino a moment to reset after the serial connection opens
    print("Connected. Watching for intrusion alerts... (Ctrl+C to stop)")

    was_active = False

    while True:
        # Check for a message from the Arduino FIRST (e.g. the physical
        # stop button was pressed) — this needs to win over whatever the
        # next status poll says, so the flag gets cleared on the server too.
        try:
            while arduino.in_waiting > 0:
                line = arduino.readline().decode(errors="ignore").strip()
                if line == "BUTTON_STOP":
                    print("\n>>> Physical button pressed — clearing alert on server.")
                    requests.get(ACK_URL, timeout=5)
                    was_active = False
        except serial.SerialException as e:
            print(f"\nSerial read error: {e}")

        try:
            resp = requests.get(STATUS_URL, timeout=5)
            data = resp.json()
            active = bool(data.get("active"))

            if active and not was_active:
                reason = data.get("reason", "unspecified")
                print(f"\n>>> ALERT STARTED: {reason}")
                arduino.write(b"1")
            elif not active and was_active:
                print("\n>>> Alert cleared (admin silenced it) — stopping alarm.")
                arduino.write(b"0")
            else:
                print(".", end="", flush=True)

            was_active = active

        except requests.exceptions.RequestException as e:
            print(f"\nCould not reach server: {e}")
            print("Check that XAMPP's Apache is running and STATUS_URL is correct.")

        time.sleep(POLL_INTERVAL_SECONDS)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\nStopped.")