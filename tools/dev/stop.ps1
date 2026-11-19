# Stoppt den lokalen Entwicklungsbetrieb, der über start.ps1 gestartet wurde.
# Aufruf: pwsh -File tools\dev\stop.ps1 [-Pause]
param([switch]$Pause)

$ErrorActionPreference = 'Stop'
$App = Resolve-Path (Join-Path $PSScriptRoot '..\..\laravel-app')
$PidFile = Join-Path $App 'storage\logs\dev-processes.json'
$HotFile = Join-Path $App 'public\hot'

function Stop-Dev {
    if (Test-Path $PidFile) {
        foreach ($entry in (Get-Content $PidFile -Raw | ConvertFrom-Json)) {
            $p = Get-Process -Id $entry.Pid -ErrorAction SilentlyContinue
            # Nur eigene Prozesse beenden: PID und Startzeit müssen zur Startdatei passen.
            if ($p -and $p.StartTime.Ticks -eq $entry.StartTicks) {
                # /T beendet auch die Unterprozesse (php -S, schedule:run, cmd).
                taskkill /T /F /PID $entry.Pid 2>&1 | Out-Null
            }
        }
        Remove-Item $PidFile
    }

    # Ein hart beendetes Vite hinterlässt public/hot; Laravel würde sonst Assets vom toten Dev-Server laden.
    if (Test-Path $HotFile) {
        Remove-Item $HotFile
    }

    Start-Sleep -Milliseconds 500
    $busy = Get-NetTCPConnection -State Listen -LocalAddress 127.0.0.1 -LocalPort 8000 -ErrorAction SilentlyContinue
    if ($busy) {
        Write-Host "Gestoppt, aber Port 8000 ist noch belegt (PID $($busy[0].OwningProcess))." -ForegroundColor Yellow
        return $false
    }

    Write-Host 'Stechen-MMO ist gestoppt.' -ForegroundColor Green
    return $true
}

$ok = Stop-Dev

if ($Pause) {
    if ($ok) { Start-Sleep -Seconds 3 } else { Read-Host 'Enter zum Schließen' | Out-Null }
}
