@echo off
echo ========================================
echo   Automated Testing with Docker
echo ========================================
echo.

echo Starting test environment...
docker-compose -f docker-compose.test.yml up -d mysql-test

echo Waiting for MySQL to be ready...
timeout /t 30 /nobreak

echo.
echo Running automated tests...
docker-compose -f docker-compose.test.yml up --abort-on-container-exit php-test

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Tests failed! Applying auto-fixes...
    docker-compose -f docker-compose.test.yml up --abort-on-container-exit auto-fix
    
    echo.
    echo Re-running tests after fixes...
    docker-compose -f docker-compose.test.yml up --abort-on-container-exit php-test
)

echo.
echo Running health check...
docker-compose -f docker-compose.test.yml run --rm health-monitor

echo.
echo Stopping test environment...
docker-compose -f docker-compose.test.yml down -v

echo.
echo ========================================
echo   Tests Complete!
echo   View results: test_dashboard.php
echo ========================================
pause
