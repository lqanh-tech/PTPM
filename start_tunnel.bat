@echo off
echo Starting Cloudflare Tunnel...
echo This will create a temporary public URL for your local server.
echo.
cloudflared tunnel --url http://localhost:8080
pause
