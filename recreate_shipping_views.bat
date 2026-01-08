@echo off
echo ========================================
echo Recreate Shipping Views
echo ========================================
echo.
echo This will recreate all shipping-related views in the database
echo.

echo Importing SQL file...
powershell -Command "Get-Content create_all_missing_views.sql | docker exec -i php_ws-mysql-1 mysql -uapp_user -papp_password sales_management"

echo.
echo Checking created views...
docker exec php_ws-mysql-1 mysql -uapp_user -papp_password sales_management -e "SHOW FULL TABLES WHERE Table_type = 'VIEW';"

echo.
echo ========================================
echo Done!
echo ========================================
pause
