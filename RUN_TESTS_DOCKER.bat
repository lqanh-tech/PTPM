@echo off
chcp 65001 >nul
color 0A
cls

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                                                            ║
echo ║        🧪 AUTOMATED TESTING - DOCKER                      ║
echo ║                                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

echo Checking Docker container...
docker ps | findstr "php_ws-web-1" >nul
if errorlevel 1 (
    echo ❌ Docker container 'php_ws-web-1' is not running!
    echo.
    echo Please start your Docker containers first:
    echo   docker-compose up -d
    echo.
    pause
    exit /b 1
)

echo ✅ Docker container is running
echo.

:menu
echo ┌────────────────────────────────────────────────────────────┐
echo │  Chọn test để chạy:                                        │
echo ├────────────────────────────────────────────────────────────┤
echo │                                                            │
echo │  1. 🚀 Quick Test (Khuyến nghị)                           │
echo │  2. 📊 Full Test Suite                                    │
echo │  3. 🔧 Fix Indexes Only                                   │
echo │  4. 🌐 Open Web Dashboard                                 │
echo │  0. ❌ Exit                                                │
echo │                                                            │
echo └────────────────────────────────────────────────────────────┘
echo.

set /p choice="Nhập lựa chọn (0-4): "

if "%choice%"=="1" goto quick_test
if "%choice%"=="2" goto full_test
if "%choice%"=="3" goto fix_indexes
if "%choice%"=="4" goto web_dashboard
if "%choice%"=="0" goto exit

echo.
echo ❌ Lựa chọn không hợp lệ!
timeout /t 2 >nul
cls
goto menu

:quick_test
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  🚀 QUICK TEST                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
docker exec php_ws-web-1 php /var/www/html/quick_test.php
echo.
echo ✅ Hoàn thành!
echo.
pause
cls
goto menu

:full_test
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  📊 FULL TEST SUITE                                       ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
docker exec php_ws-web-1 php /var/www/html/automated_test_runner.php
echo.
echo ✅ Hoàn thành!
echo.
pause
cls
goto menu

:fix_indexes
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  🔧 FIX INDEXES                                           ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo ⚠️  CẢNH BÁO: Thao tác này sẽ thay đổi database!
echo.
set /p confirm="Bạn có chắc chắn muốn tiếp tục? (Y/N): "
if /i not "%confirm%"=="Y" (
    echo.
    echo ❌ Đã hủy thao tác.
    timeout /t 2 >nul
    cls
    goto menu
)
echo.
start http://localhost:20080/auto_fix_indexes.php
echo.
echo ✅ Đã mở trang fix indexes trong browser!
echo.
pause
cls
goto menu

:web_dashboard
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  🌐 WEB DASHBOARD                                         ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Đang mở dashboard...
echo.
start http://localhost:20080/test_dashboard.php
timeout /t 2 >nul
echo ✅ Dashboard đã được mở!
echo.
echo URL: http://localhost:20080/test_dashboard.php
echo.
pause
cls
goto menu

:exit
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                                                            ║
echo ║        👋 Cảm ơn bạn đã sử dụng!                         ║
echo ║                                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
timeout /t 2 >nul
exit
