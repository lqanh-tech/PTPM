@echo off
echo ========================================
echo Starting PHP Server and Cloudflare Tunnel
echo ========================================
echo.

REM Start PHP Server in background
echo [1/2] Starting PHP Server on localhost:8081...
start "PHP Server" cmd /k "php -S localhost:8081 -t lequocanh"
timeout /t 3 /nobreak >nul

REM Start Cloudflare Tunnel
echo [2/2] Starting Cloudflare Tunnel...
echo.
echo ========================================
echo COPY THE TUNNEL URL BELOW:
echo ========================================
cloudflared.exe tunnel --url http://localhost:8081

pause
