@echo off
echo ========================================
echo  DATABASE BACKUP SCRIPT
echo ========================================
echo.

set TIMESTAMP=%DATE:~-4%%DATE:~4,2%%DATE:~7,2%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_FILE=docker_mysql_backup_%TIMESTAMP%.sql
set BACKUP_PATH=%~dp0backups\%BACKUP_FILE%

if not exist "%~dp0backups" mkdir "%~dp0backups"

echo [1/4] Checking Docker...
docker ps >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Docker is not running or not installed!
    pause
    exit /b 1
)
echo Docker OK.

echo.
echo [2/4] Finding MySQL container...
set MYSQL_CONTAINER=

:: Try common container names from docker-compose
for %%n in ("php_ws-mysql-1" "php_ws_mysql_1" "mysql") do (
    docker inspect %%~n >nul 2>&1
    if !ERRORLEVEL! EQU 0 (
        set MYSQL_CONTAINER=%%~n
        goto :found
    )
)

:: Fallback: search all containers for mysql image
for /f "tokens=1" %%i in ('docker ps -a --filter "ancestor=mysql" --format "{{.Names}}" 2^>nul') do (
    set MYSQL_CONTAINER=%%i
    goto :found
)

echo ERROR: MySQL container not found!
pause
exit /b 1

:found.
echo MySQL container: %MYSQL_CONTAINER%

echo.
echo [3/4] Dumping all databases...
docker exec %MYSQL_CONTAINER% mysqldump -u root --password=root --all-databases --single-transaction --routines --triggers --events > "%BACKUP_PATH%"
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Backup failed!
    pause
    exit /b 1
)

echo Backup saved: %BACKUP_PATH%

echo.
echo [4/4] Verifying backup...
for %%A in ("%BACKUP_PATH%") do set SIZE=%%~zA
echo Backup size: %SIZE% bytes

if %SIZE% LSS 1024 (
    echo WARNING: Backup file seems too small, might be corrupted!
) else (
    echo Backup OK.
)

echo.
echo ========================================
echo  BACKUP COMPLETE
echo  File: %BACKUP_PATH%
echo ========================================
pause
