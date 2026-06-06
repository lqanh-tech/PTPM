#Requires -Version 5.1
$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " FULL DOCKER REBUILD SCRIPT" -ForegroundColor Cyan  
Write-Host "========================================" -ForegroundColor Cyan

$null = New-Item -ItemType Directory -Force -Path "$PSScriptRoot\backups"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = "docker_mysql_backup_$timestamp.sql"
$backupPath = Join-Path "$PSScriptRoot\backups" $backupFile

# ============================================================
# STEP 1: Check Docker
# ============================================================
Write-Host "`n[1/6] Checking Docker..." -ForegroundColor Yellow
try {
    $null = docker ps 2>&1
    Write-Host "Docker OK." -ForegroundColor Green
} catch {
    Write-Host "ERROR: Docker not found or not running!" -ForegroundColor Red
    Write-Host "Please start Docker Desktop first, then run this script again." -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# ============================================================
# STEP 2: Find MySQL container
# ============================================================
Write-Host "`n[2/6] Finding MySQL container..." -ForegroundColor Yellow
$mysqlContainer = $null
$allContainers = docker ps -a --format "{{.Names}} | {{.Status}} | {{.Image}}" 2>&1
Write-Host "All containers:" -ForegroundColor Gray
$allContainers | ForEach-Object { Write-Host "  $_" -ForegroundColor Gray }

foreach ($name in @("php_ws-mysql-1", "php_ws_mysql_1", "mysql")) {
    $null = docker inspect $name 2>&1
    if ($LASTEXITCODE -eq 0) { $mysqlContainer = $name; break }
}
if (-not $mysqlContainer) {
    $mysqlContainer = docker ps -a --filter "ancestor=mysql" --format "{{.Names}}" | Select-Object -First 1
}

if (-not $mysqlContainer) {
    Write-Host "`nNo MySQL container found. Will create fresh with docker-compose." -ForegroundColor Yellow
    Write-Host "`n[3/6] Starting all containers..." -ForegroundColor Yellow
    docker-compose up -d 2>&1 | ForEach-Object { Write-Host "  $_" }
    Start-Sleep -Seconds 15
    
    foreach ($name in @("php_ws-mysql-1", "php_ws_mysql_1", "mysql")) {
        $null = docker inspect $name 2>&1
        if ($LASTEXITCODE -eq 0) { $mysqlContainer = $name; break }
    }
    if (-not $mysqlContainer) {
        $mysqlContainer = docker ps -a --filter "ancestor=mysql" --format "{{.Names}}" | Select-Object -First 1
    }
    if (-not $mysqlContainer) {
        Write-Host "ERROR: Still no MySQL container!" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}
Write-Host "MySQL container: $mysqlContainer" -ForegroundColor Green

# ============================================================
# STEP 3: Start MySQL if stopped
# ============================================================
Write-Host "`n[3/6] Ensuring MySQL is running..." -ForegroundColor Yellow
$running = docker inspect --format "{{.State.Running}}" $mysqlContainer 2>&1
if ($running -ne "true") {
    Write-Host "  Starting $mysqlContainer..." -ForegroundColor Gray
    docker start $mysqlContainer 2>&1 | Out-Null
    Start-Sleep -Seconds 10
    $running = docker inspect --format "{{.State.Running}}" $mysqlContainer 2>&1
    if ($running -ne "true") {
        Write-Host "ERROR: Cannot start MySQL container!" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

# Wait for MySQL ready
Write-Host "  Waiting for MySQL to respond..." -ForegroundColor Gray
$maxRetries = 30
$ready = $false
for ($i = 1; $i -le $maxRetries; $i++) {
    $null = docker exec $mysqlContainer mysqladmin ping -u root --password=root --silent 2>&1
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Write-Host "  Waiting... ($i/$maxRetries)" -ForegroundColor Gray
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    Write-Host "ERROR: MySQL not responding after $maxRetries attempts!" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host "MySQL is ready." -ForegroundColor Green

# ============================================================
# STEP 4: Backup
# ============================================================
Write-Host "`n[4/6] Backing up database..." -ForegroundColor Yellow
Write-Host "  Dumping all databases..." -ForegroundColor Gray
docker exec $mysqlContainer mysqldump -u root --password=root --all-databases --single-transaction --routines --triggers --events 2>$null | Out-File -FilePath $backupPath -Encoding utf8

if ($LASTEXITCODE -ne 0) {
    Write-Host "WARNING: Backup may have errors, checking..." -ForegroundColor Yellow
    $content = Get-Content $backupPath -Raw 2>$null
    if ($content -match "Got error|Access denied|Unknown database") {
        Write-Host "ERROR: Backup failed with: $($content.Substring(0, [Math]::Min(200, $content.Length)))" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

$size = (Get-Item $backupPath).Length
Write-Host "  Backup file: $backupPath" -ForegroundColor Cyan
Write-Host "  Backup size: $size bytes ($([Math]::Round($size/1024, 1)) KB)" -ForegroundColor Cyan

if ($size -lt 500) {
    Write-Host "  WARNING: Backup seems too small!" -ForegroundColor Yellow
    Write-Host "  Content:" -ForegroundColor Yellow
    Get-Content $backupPath | ForEach-Object { Write-Host "    $_" -ForegroundColor Yellow }
    
    $continue = Read-Host "  Continue anyway? (y/n)"
    if ($continue -ne "y") { exit 0 }
} else {
    Write-Host "  Backup OK!" -ForegroundColor Green
}

# ============================================================
# STEP 5: Rebuild
# ============================================================
Write-Host "`n[5/6] Rebuilding containers (this will DELETE old MySQL data)..." -ForegroundColor Red
$confirm = Read-Host "  Type 'yes' to confirm destroy + rebuild"
if ($confirm -ne "yes") {
    Write-Host "Aborted. Backup is saved at: $backupPath" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 0
}

Write-Host "  Stopping and removing containers + volumes..." -ForegroundColor Gray
docker-compose down -v 2>&1 | ForEach-Object { Write-Host "  $_" }

Write-Host "  Starting fresh containers..." -ForegroundColor Gray
docker-compose up -d 2>&1 | ForEach-Object { Write-Host "  $_" }

# Wait for MySQL to be healthy
Write-Host "`n  Waiting for MySQL to be ready (up to 60s)..." -ForegroundColor Gray
$ready = $false
for ($i = 1; $i -le 30; $i++) {
    $null = docker exec $mysqlContainer mysqladmin ping -u root --password=root --silent 2>&1
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Write-Host "  Waiting... ($i/30)" -ForegroundColor Gray
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    Write-Host "ERROR: MySQL not responding after rebuild!" -ForegroundColor Red
    Write-Host "Check logs: docker logs $mysqlContainer" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host "  MySQL is ready." -ForegroundColor Green

# ============================================================
# STEP 6: Restore + Verify
# ============================================================
Write-Host "`n[6/6] Restoring backup..." -ForegroundColor Yellow
if ($size -gt 500) {
    Get-Content $backupPath | docker exec -i $mysqlContainer mysql -u root --password=root 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  Restore OK!" -ForegroundColor Green
    } else {
        Write-Host "  WARNING: Restore had errors (may be OK for fresh DB)" -ForegroundColor Yellow
    }
} else {
    Write-Host "  Skipping restore (backup too small or empty)" -ForegroundColor Yellow
}

# Verify tables
Write-Host "`n  Verifying database tables..." -ForegroundColor Cyan
docker exec $mysqlContainer mysql -u root --password=root -e "USE sales_management; SHOW TABLES;" 2>&1 | ForEach-Object { Write-Host "  $_" }

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host " ALL DONE!" -ForegroundColor Green
Write-Host " Backup: $backupPath" -ForegroundColor Cyan
Write-Host " MySQL:  $mysqlContainer (running)" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "`nNext steps:" -ForegroundColor Yellow
Write-Host "  1. Open http://localhost:20080 in browser" -ForegroundColor White
Write-Host "  2. Check for errors in browser" -ForegroundColor White
Write-Host "  3. phpMyAdmin: http://localhost:28888" -ForegroundColor White
Read-Host "`nPress Enter to exit"
