@echo off
REM Ward Import - Complete Execution Script
REM This script runs all import steps automatically

echo ========================================
echo Ward/Commune Import - Automated Script
echo ========================================
echo.

REM Find PHP executable
set PHP_PATH=
if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php\php.exe
if exist "C:\wamp64\bin\php\php7.4.9\php.exe" set PHP_PATH=C:\wamp64\bin\php\php7.4.9\php.exe
if exist "C:\php\php.exe" set PHP_PATH=C:\php\php.exe

if "%PHP_PATH%"=="" (
    echo ERROR: PHP not found!
    echo Please install PHP or update paths in this script.
    pause
    exit /b 1
)

echo Using PHP: %PHP_PATH%
echo.

cd /d d:\PHP_WS\lequocanh\database

REM Step 1: Check database
echo [Step 1/5] Checking database connection...
"%PHP_PATH%" check_db.php
if errorlevel 1 (
    echo ERROR: Database check failed!
    pause
    exit /b 1
)
echo.

REM Step 2: Analyze CSV (optional)
echo [Step 2/5] Analyzing CSV data...
"%PHP_PATH%" analyze_csv_data.php
echo.

REM Step 3: Import provinces and districts
echo [Step 3/5] Importing provinces and districts...
echo Running dry-run first...
"%PHP_PATH%" import_provinces_districts.php --dry-run
echo.
echo Press any key to execute actual import, or Ctrl+C to cancel
pause
"%PHP_PATH%" import_provinces_districts.php --execute
if errorlevel 1 (
    echo ERROR: Province/District import failed!
    pause
    exit /b 1
)
echo.

REM Step 4: Import wards
echo [Step 4/5] Importing wards/communes (3,325 records)...
echo This may take a few minutes...
echo.
echo Running dry-run first...
"%PHP_PATH%" import_wards_from_csv.php --dry-run
echo.
echo Press any key to execute actual import, or Ctrl+C to cancel
pause
"%PHP_PATH%" import_wards_from_csv.php --execute
if errorlevel 1 (
    echo ERROR: Ward import failed!
    pause
    exit /b 1
)
echo.

REM Step 5: Validate and generate report
echo [Step 5/5] Validating import and generating report...
"%PHP_PATH%" validate_ward_import.php
echo.

REM Open HTML report
if exist "ward_import_report.html" (
    echo Opening HTML report...
    start ward_import_report.html
)

echo.
echo ========================================
echo Import completed successfully!
echo ========================================
echo.
echo Check the HTML report for details.
echo Log file: import.log
echo.
pause
