@echo off
echo ================================================================
echo           AUTO OPTIMIZATION - CHAY TU DONG
echo ================================================================
echo.

REM Tim PHP executable
set PHP_PATH=
if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php\php.exe
if exist "C:\wamp64\bin\php\php8.2.0\php.exe" set PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe
if exist "C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe" set PHP_PATH=C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe

if "%PHP_PATH%"=="" (
    echo [ERROR] Khong tim thay PHP! Vui long cai dat XAMPP/WAMP/Laragon
    echo.
    echo Hoac chay truc tiep qua browser:
    echo http://localhost/run_optimization_simple.php
    pause
    exit /b 1
)

echo [INFO] Tim thay PHP tai: %PHP_PATH%
echo.

echo ================================================================
echo BUOC 1: TAO DATABASE INDEXES
echo ================================================================
echo.

"%PHP_PATH%" run_optimization_simple.php

echo.
echo ================================================================
echo BUOC 2: AP DUNG SERVICES (Optional - can chay thu cong)
echo ================================================================
echo.
echo De ap dung Services, chay:
echo   php auto_implement_services.php
echo.
echo Hoac truy cap qua browser:
echo   http://localhost/auto_implement_services.php
echo.

echo ================================================================
echo HOAN THANH!
echo ================================================================
echo.
echo Ket qua da duoc luu vao file tren.
echo.
echo Buoc tiep theo:
echo 1. Kiem tra ket qua tren
echo 2. Test website
echo 3. Chay auto_implement_services.php de ap dung Services
echo.

pause
