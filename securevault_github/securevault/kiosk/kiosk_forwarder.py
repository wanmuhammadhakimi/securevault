"""
SecureVault - laptop serial forwarder

Keeps the Arduino's COM port open on the laptop and exposes it on
127.0.0.1:7000 (this laptop only). An SSH reverse tunnel carries that port
to the Raspberry Pi, where kiosk_bridge.py connects to socket://127.0.0.1:7000.

  Arduino --USB--> laptop (this script) --SSH tunnel--> Pi (kiosk_bridge.py)

Install:  pip install pyserial
Run:      python kiosk_forwarder.py
Then, in a second CMD window, start the tunnel:
          ssh -N -R 7000:127.0.0.1:7000 user@raspberrypi.local
"""
import socket
import threading
import time

import serial

SERIAL_PORT = "COM4"
SERIAL_BAUD = 115200
LISTEN_HOST = "127.0.0.1"   # never 0.0.0.0 - the dorm WiFi is open
LISTEN_PORT = 7000


def log(msg):
    print(time.strftime("[%H:%M:%S] ") + msg, flush=True)


def open_serial():
    while True:
        try:
            ser = serial.Serial(SERIAL_PORT, SERIAL_BAUD, timeout=0.1)
            log(f"Opened {SERIAL_PORT}")
            return ser
        except serial.SerialException as e:
            log(f"Cannot open {SERIAL_PORT}: {e} (close Serial Monitor / other scripts)")
            time.sleep(3)


def serial_to_socket(ser, conn, stop):
    while not stop.is_set():
        try:
            data = ser.read(256)
            if data:
                conn.sendall(data)
        except (OSError, serial.SerialException):
            stop.set()


def socket_to_serial(ser, conn, stop):
    while not stop.is_set():
        try:
            data = conn.recv(1024)
            if not data:
                break
            ser.write(data)
        except (OSError, serial.SerialException):
            break
    stop.set()


def main():
    ser = open_serial()
    srv = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    srv.bind((LISTEN_HOST, LISTEN_PORT))
    srv.listen(1)
    log(f"Forwarding {SERIAL_PORT} on {LISTEN_HOST}:{LISTEN_PORT} - waiting for the Pi bridge...")

    while True:
        conn, addr = srv.accept()
        conn.setsockopt(socket.IPPROTO_TCP, socket.TCP_NODELAY, 1)
        log("Pi bridge connected")
        ser.reset_input_buffer()
        stop = threading.Event()
        t = threading.Thread(target=serial_to_socket, args=(ser, conn, stop), daemon=True)
        t.start()
        socket_to_serial(ser, conn, stop)
        t.join(timeout=1)
        conn.close()
        log("Pi bridge disconnected - waiting again...")
        if not ser.is_open:
            ser = open_serial()


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        log("Stopped.")
