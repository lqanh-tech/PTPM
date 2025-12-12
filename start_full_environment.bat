@echo off
echo ========================================
echo Starting Full Development Environment
echo ========================================
echo.
echo This will start:
echo  - PHP built-in server (port 8080)
echo  - MySQL server (port 3306)
echo.
echo To stop, close this window or press Ctrl+C
echo.

REM Start PHP server in background
echo Starting PHP server...
start "PHP Server" /D "d:\PHP_WS" D:\php-8.3.9\php.exe -S localhost:8080 -t lequocanh

REM You can install MySQL separately or use XAMPP if you have it
echo.
echo PHP server started on http://localhost:8080
echo.
echo To access your application:
echo  - Main app: http://localhost:8080
echo  - Bcrypt test: http://localhost:8080/administrator/bcrypt_test_standalone.php
echo.
echo To make your site public (Cloudflare Tunnel):
echo  - Run: start_tunnel.bat
echo.
echo Press any key to continue...
pause >nul