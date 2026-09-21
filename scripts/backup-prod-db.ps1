<#
Pulls a full copy of the production database (Neon/Postgres) and restores
it into a local MySQL database, so there is always a recent local copy
browsable via phpMyAdmin - independent of the local dev database used day
to day.

Delegates the actual copy to the `backup:neon-to-local` artisan command,
which reads connection details from .env.deploy (gitignored, never
committed) and copies table data through Laravel's query builder - this
avoids pg_dump/mysql format incompatibilities between the two engines.

Usage:  powershell -File scripts\backup-prod-db.ps1
#>

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$logDir = Join-Path $repoRoot "storage\backups"
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
$logFile = Join-Path $logDir ("backup_" + (Get-Date -Format "yyyy-MM-dd_HHmmss") + ".log")

Push-Location $repoRoot
try {
    & php artisan backup:neon-to-local *>&1 | Tee-Object -FilePath $logFile

    if ($LASTEXITCODE -ne 0) {
        Write-Error "backup:neon-to-local failed (exit $LASTEXITCODE) - see $logFile"
        exit 1
    }
}
finally {
    Pop-Location
}
