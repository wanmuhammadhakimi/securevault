@echo off
REM SecureVault kiosk - starts the serial forwarder and the SSH tunnel to the Pi.
REM Keep this window open. Close it (and the forwarder window) to stop the kiosk.

set PI=user@raspberrypi.local

cd /d "%~dp0"
start "SecureVault forwarder" python kiosk_forwarder.py
timeout /t 3 >nul

:tunnel
echo [%time%] Opening tunnel to %PI% ...
ssh -N -o ServerAliveInterval=15 -o ServerAliveCountMax=3 -o ExitOnForwardFailure=yes -R 7000:127.0.0.1:7000 %PI%
echo [%time%] Tunnel dropped - reconnecting in 5 seconds...
timeout /t 5 >nul
goto tunnel
