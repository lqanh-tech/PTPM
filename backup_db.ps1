#Requires -Version 5.1
$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " DATABASE BACKUP SCRIPT (PowerShell)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
$null = New-Item -ItemType Directory -Force -Path "$PSScriptRoot\backups"

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "docker_mysql_backup_$timestamp.sql"
$backupPath = Join-Path "$PSScriptRoot\backups" $backupFile

# 1. Check Docker
Write-Host "`n[1/4] Checking Docker..." -ForegroundColor Yellow
try {
    $null = docker ps 2>&1
    Write-Host "Docker OK." -ForegroundColor Green
} catch {
    Write-Host "ERROR: Docker not found!" -ForegroundColor Red
    exit 1
}

# 2. Find MySQL container
Write-Host "`n[2/4] Finding MySQL container..." -ForegroundColor Yellow
$containerNames = @("php_ws-mysql-1", "php_ws_mysql_1", "mysql")
$mysqlContainer = $null

foreach ($name in $containerNames) {
    $result = docker inspect $name 2>&1
    if ($LASTEXITCODE -eq 0) {
        $mysqlContainer = $name
        break
    }
}

if (-not $mysqlContainer) {
    # Fallback: search by image
    $mysqlContainer = docker ps -a --filter "ancestor=mysql" --format "{{.Names}}" | Select-Object -First 1
}

if (-not $mysqlContainer) {
    Write-Host "ERROR: MySQL container not found!" -ForegroundColor Red
    Write-Host "Available containers:" -ForegroundColor Gray
    docker ps -a --format "  {{.Names}} | {{.Status}} | {{.Image}}"
    exit 1
}

Write-Host "MySQL container: $mysqlContainer" -ForegroundColor Green

# 3. Check if container is running
$status = docker inspect --format "{{.State.Running}}" $mysqlContainer 2>&1
if ($status -ne "true") {
    Write-Host "`nMySQL container is NOT running. Starting it..." -ForegroundColor Yellow
    docker start $mysqlContainer 2>&1 | Out-Null
    Start-Sleep -Seconds 10
    $status = docker inspect --format "{{.State.Running}}" $mysqlContainer 2>&1
    if ($status -ne "true") {
        Write-Host "ERROR: Failed to start MySQL container!" -ForegroundColor Red
        exit 1
    }
    Write-Host "MySQL container started." -ForegroundColor Green
}

# 4. Wait for MySQL to be ready
Write-Host "`n[3/4] Waiting for MySQL to be ready..." -ForegroundColor Yellow
$maxRetries = 30
$ready = $false
for ($i = 0; $i -lt $maxRetries; $i++) {
    try {
        $result = docker exec $mysqlContainer mysqladmin ping -u root --password=root --silent 2>&1
        if ($LASTEXITCODE -eq 0) {
            $ready = $true
            break
        }
    } catch {}
    Write-Host "  Waiting... ($i/$maxRetries)" -ForegroundColor Gray
    Start-Sleep -Seconds 2
}

if (-not $ready) {
    Write-Host "ERROR: MySQL not responding after $maxRetries attempts!" -ForegroundColor Red
    exit 1
}
Write-Host "MySQL is ready." -ForegroundColor Green

# 5. List databases
Write-Host "`nDatabases found:" -ForegroundColor Cyan
docker exec $mysqlContainer mysql -u root --password=root -e "SHOW DATABASES;" 2>&1 | ForEach-Object { Write-Host "  $_" }

# 6. Backup
Write-Host "`n[4/4] Dumping all databases..." -ForegroundColor Yellow
docker exec $mysqlContainer mysqldump -u root --password=root --all-databases --single-transaction --routines --triggers --events > $backupPath 2>&1

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Backup failed!" -ForegroundColor Red
    Get-Content $backupPath | ForEach-Object { Write-Host $_ -ForegroundColor Red }
    exit 1
}

$size = (Get-Item $backupPath).Length
Write-Host "Backup saved: $backupPath" -ForegroundColor Green
Write-Host "Backup size: $size bytes" -ForegroundColor Green

if ($size -lt 1024) {
    Write-Host "WARNING: Backup file seems too small!" -ForegroundColor Yellow
    Get-Content $backupPath | ForEach-Object { Write-Host $_ -ForegroundColor Yellow }
} else {
    Write-Host "`nBackup OK." -ForegroundColor Green
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host " BACKUP COMPLETE" -ForegroundColor Green
Write-Host " File: $backupPath" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
