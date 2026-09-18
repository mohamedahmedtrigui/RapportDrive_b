<#
Pulls a full dump of the production database (Aiven) and restores it into a
local MySQL database, so there is always a recent local copy browsable via
phpMyAdmin - independent of the local dev database used day to day.

Reads connection details from .env.deploy (gitignored, never committed).
Run from anywhere; paths are resolved relative to this script.

Usage:  powershell -File scripts\backup-prod-db.ps1
#>

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $repoRoot ".env.deploy"

if (-not (Test-Path $envFile)) {
    Write-Error ".env.deploy not found at $envFile - fill it in first (see .env.deploy)."
    exit 1
}

$envVars = @{}
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^\s*([A-Z_]+)=(.*)$') {
        $envVars[$matches[1]] = $matches[2].Trim()
    }
}

foreach ($key in "DB_HOST", "DB_PORT", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD") {
    if (-not $envVars[$key]) {
        Write-Error "$key is missing or empty in .env.deploy."
        exit 1
    }
}

# Adjust this if MariaDB/MySQL client tools live elsewhere on your machine.
$mysqlBin = "C:\wamp64\bin\mariadb\mariadb11.4.9\bin"
$pluginDir = "C:\wamp64\bin\mariadb\mariadb11.4.9\lib\plugin"
$localDb = "rapportdrive_prod_backup"

$backupDir = Join-Path $repoRoot "storage\backups"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$timestamp = Get-Date -Format "yyyy-MM-dd_HHmmss"
$dumpFile = Join-Path $backupDir "backup_$timestamp.sql"

Write-Host "Dumping production database '$($envVars.DB_DATABASE)' from Aiven..."
& "$mysqlBin\mysqldump.exe" `
    -h $envVars.DB_HOST -P $envVars.DB_PORT -u $envVars.DB_USERNAME "-p$($envVars.DB_PASSWORD)" `
    --ssl --ssl-verify-server-cert=0 --plugin-dir=$pluginDir `
    --single-transaction --routines --triggers `
    --result-file="$dumpFile" `
    $envVars.DB_DATABASE

if ($LASTEXITCODE -ne 0) {
    Write-Error "mysqldump failed (exit $LASTEXITCODE)."
    exit 1
}

Write-Host "Restoring into local database '$localDb'..."
& "$mysqlBin\mysql.exe" -h 127.0.0.1 -u root -e `
    "DROP DATABASE IF EXISTS $localDb; CREATE DATABASE $localDb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Get-Content $dumpFile -Raw | & "$mysqlBin\mysql.exe" -h 127.0.0.1 -u root $localDb

if ($LASTEXITCODE -ne 0) {
    Write-Error "Restore into local MySQL failed (exit $LASTEXITCODE)."
    exit 1
}

Write-Host ""
Write-Host "Done."
Write-Host "  Dump file : $dumpFile"
Write-Host "  Local DB  : $localDb (browse it in phpMyAdmin at http://localhost/phpmyadmin)"
