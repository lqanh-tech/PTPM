@echo off
chcp 65001 >nul
color 0A
cls

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                                                            ║
echo ║        🧪 AUTOMATED TESTING SYSTEM                        ║
echo ║           LeQuocAnh Project                                ║
echo ║                                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo.

:menu
echo ┌────────────────────────────────────────────────────────────┐
echo │  Chọn phương pháp test:                                    │
echo ├────────────────────────────────────────────────────────────┤
echo │                                                            │
echo │  1. 🚀 Quick Test (Khuyến nghị)                           │
echo │     → Chạy test và auto-fix trong một lệnh                │
echo │                                                            │
echo │  2. 🐳 Docker Test (Production)                           │
echo │     → Chạy trong môi trường Docker isolated               │
echo │                                                            │
echo │  3. 🌐 Open Web Dashboard                                 │
echo │     → Mở dashboard trong browser                          │
echo │                                                            │
echo │  4. 🔧 Fix Indexes Only                                   │
echo │     → Chỉ sửa database indexes                            │
echo │                                                            │
echo │  5. ⚡ Test Optimizations                                 │
echo │     → Kiểm tra các tối ưu hóa                             │
echo │                                                            │
echo │  6. 📚 Open Documentation                                 │
echo │     → Xem hướng dẫn chi tiết                              │
echo │                                                            │
echo │  0. ❌ Exit                                                │
echo │                                                            │
echo └────────────────────────────────────────────────────────────┘
echo.

set /p choice="Nhập lựa chọn của bạn (0-6): "

if "%choice%"=="1" goto quick_test
if "%choice%"=="2" goto docker_test
if "%choice%"=="3" goto web_dashboard
if "%choice%"=="4" goto fix_indexes
if "%choice%"=="5" goto test_optimizations
if "%choice%"=="6" goto documentation
if "%choice%"=="0" goto exit

echo.
echo ❌ Lựa chọn không hợp lệ! Vui lòng chọn từ 0-6.
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
echo Đang chạy quick test trong Docker container...
echo.

docker exec php_ws-web-1 php /var/www/html/quick_test.php
echo.
echo ✅ Hoàn thành!
echo.
pause
cls
goto menu

:docker_test
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  🐳 DOCKER TEST                                           ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Kiểm tra Docker...
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker chưa được cài đặt!
    echo.
    echo Vui lòng cài đặt Docker Desktop từ:
    echo https://www.docker.com/products/docker-desktop
    echo.
    pause
    cls
    goto menu
)
echo ✅ Docker đã sẵn sàng
echo.
echo Đang chạy tests với Docker...
echo.
call run_docker_tests.bat
echo.
echo ✅ Hoàn thành!
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
echo Đang mở dashboard trong browser...
echo.
start http://localhost/test_dashboard.php
timeout /t 2 >nul
echo ✅ Dashboard đã được mở!
echo.
echo Nếu không tự động mở, vui lòng truy cập:
echo http://localhost/test_dashboard.php
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
echo Đang sửa indexes...
echo.
start http://localhost/auto_fix_indexes.php
timeout /t 2 >nul
echo.
echo ✅ Đã mở trang fix indexes trong browser!
echo.
pause
cls
goto menu

:test_optimizations
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  ⚡ TEST OPTIMIZATIONS                                    ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Đang mở trang test optimizations...
echo.
start http://localhost/test_optimizations.php
timeout /t 2 >nul
echo ✅ Đã mở trang test!
echo.
pause
cls
goto menu

:documentation
cls
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  📚 DOCUMENTATION                                         ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Đang mở documentation...
echo.
start START_TESTING.html
start AUTOMATED_TESTING_README.md
start TESTING_SUMMARY.md
timeout /t 2 >nul
echo ✅ Đã mở tài liệu!
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
echo ║           Automated Testing System                         ║
echo ║                                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
timeout /t 2 >nul
exit
