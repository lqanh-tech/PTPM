@echo off
echo ========================================
echo Cloudflare Tunnel for XAMPP/WAMP
echo ========================================
echo.
echo IMPORTANT: Make sure Apache is running first!
echo.
echo Which port is your Apache running on?
echo [1] Port 80 (default)
echo [2] Port 8080
echo [3] Port 8081
echo [4] Custom port
echo.
set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" (
    set PORT=80
) else if "%choice%"=="2" (
    set PORT=8080
) else if "%choice%"=="3" (
    set PORT=8081
) else if "%choice%"=="4" (
    set /p PORT="Enter custom port: "
) else (
    echo Invalid choice! Using default port 80
    set PORT=80
)

echo.
echo ========================================
echo Starting Cloudflare Tunnel on port %PORT%
echo ========================================
echo.
echo COPY THE TUNNEL URL BELOW:
echo ========================================
echo.

cloudflared.exe tunnel --url http://localhost:%PORT%/lequocanh

pause
