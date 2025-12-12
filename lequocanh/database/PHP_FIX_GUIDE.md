# PHP Extension Issue - Quick Fix Guide

## Problem
PHP `pdo_mysql` extension is not enabled, preventing database connection

## Found PHP Installations
- ✅ `D:\php-8.3.9\php.exe` - Has `php_pdo_mysql.dll` in ext folder
- `D:\php-8.4.6-nts-Win32-vs17-x64\php.exe`

## Solution 1: Enable Extension (Recommended)

### Steps:
1. Open `D:\php-8.3.9\php.ini` in a text editor
2. Find this line:
   ```
   ;extension=pdo_mysql
   ```
3. Remove the semicolon (uncomment):
   ```
   extension=pdo_mysql
   ```
4. Save the file
5. Verify by running:
   ```
   D:\php-8.3.9\php.exe -m | findstr pdo_mysql
   ```

If you don't have `php.ini`, copy `php.ini-development` to `php.ini` first.

## Solution 2: Use SQL Import Instead

I can generate SQL files that you can import directly via phpMyAdmin or MySQL command line.

## Solution 3: Provide XAMPP/WAMP PHP Path

If you have XAMPP or WAMP installed, provide the PHP path and I'll use that instead.

## After Fixing

Run this command to verify:
```
D:\php-8.3.9\php.exe lequocanh/database/test_connection.php
```

Should output:
```
✓ PDO MySQL extension is loaded
✓ MySQL connection successful!
```
