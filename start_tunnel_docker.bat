@echo off
echo ========================================
echo Cloudflare Tunnel for Docker Setup
echo ========================================
echo.
echo Checking Docker containers...
docker ps --format "table {{.Names}}\t{{.Ports}}" --filter "name=apache"
echo.
echo ========================================
echo Starting Cloudflare Tunnel on port 80
echo ========================================
echo.
echo COPY THE TUNNEL URL BELOW:
echo ========================================
echo.

cloudflared.exe tunnel --url http://localhost:80

pause
