# Startet den lokalen Entwicklungsbetrieb (Webserver, Scheduler, Vite) unsichtbar im Hintergrund.
# Aufruf: pwsh -File tools\dev\start.ps1 [-Pause]
param([switch]$Pause)

$ErrorActionPreference = 'Stop'
$App = Resolve-Path (Join-Path $PSScriptRoot '..\..\laravel-app')
$Logs = Join-Path $App 'storage\logs'
$PidFile = Join-Path $Logs 'dev-processes.json'
$Url = 'http://127.0.0.1:8000'

# Jeder Prozess bekommt eine versteckte Konsole. Ohne Konsole (z. B. unter PM2) öffnen die
# Unterprozesse von artisan serve und schedule:work sichtbare cmd-Fenster.
$Processes = @(
    @{ Name = 'web'; File = 'php'; Args = @('artisan', 'serve', '--host=127.0.0.1', '--port=8000') },
    @{ Name = 'scheduler'; File = 'php'; Args = @('artisan', 'schedule:work') },
    @{ Name = 'vite'; File = 'node'; Args = @('node_modules\vite\bin\vite.js') }
)

function Get-RunningDevProcesses {
    if (-not (Test-Path $PidFile)) { return @() }
    $entries = Get-Content $PidFile -Raw | ConvertFrom-Json
    # Startzeit mitprüfen, damit eine wiederverwendete PID nicht als unser Prozess gilt.
    return @($entries | Where-Object {
        $p = Get-Process -Id $_.Pid -ErrorAction SilentlyContinue
        $p -and $p.StartTime.Ticks -eq $_.StartTicks
    })
}

function Start-Dev {
    $db = Get-Service -Name MariaDB -ErrorAction SilentlyContinue
    if ($null -eq $db -or $db.Status -ne 'Running') {
        Write-Host 'Der Windows-Dienst MariaDB läuft nicht.' -ForegroundColor Red
        return $false
    }

    if ((Get-RunningDevProcesses).Count -gt 0) {
        Write-Host "Läuft bereits: $Url (sonst zuerst stop.ps1 ausführen)" -ForegroundColor Yellow
        return $true
    }

    $busy = Get-NetTCPConnection -State Listen -LocalAddress 127.0.0.1 -LocalPort 8000 -ErrorAction SilentlyContinue
    if ($busy) {
        $owner = (Get-Process -Id $busy[0].OwningProcess).ProcessName
        Write-Host "Port 8000 ist bereits belegt (Prozess: $owner, PID $($busy[0].OwningProcess))." -ForegroundColor Red
        return $false
    }

    $started = foreach ($proc in $Processes) {
        $p = Start-Process -FilePath $proc.File -ArgumentList $proc.Args -WorkingDirectory $App `
            -WindowStyle Hidden -PassThru `
            -RedirectStandardOutput (Join-Path $Logs "dev-$($proc.Name).out.log") `
            -RedirectStandardError (Join-Path $Logs "dev-$($proc.Name).err.log")
        # Ticks statt Datumstext: ConvertFrom-Json wandelt ISO-Datumstexte automatisch in DateTime um.
        @{ Name = $proc.Name; Pid = $p.Id; StartTicks = $p.StartTime.Ticks }
    }
    $started | ConvertTo-Json | Set-Content -Path $PidFile -Encoding utf8

    $deadline = (Get-Date).AddSeconds(30)
    while ((Get-Date) -lt $deadline) {
        try {
            $response = Invoke-WebRequest -Uri $Url -TimeoutSec 2 -SkipHttpErrorCheck -UseBasicParsing
            if ($response.StatusCode -lt 500) {
                Write-Host "Stechen-MMO läuft: $Url" -ForegroundColor Green
                return $true
            }
            Write-Host "Webserver antwortet mit HTTP $($response.StatusCode). Logs: $Logs" -ForegroundColor Red
            return $false
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }

    Write-Host "Keine Antwort von $Url nach 30 Sekunden. Logs: $Logs (dev-*.log)" -ForegroundColor Red
    return $false
}

$ok = Start-Dev

if ($Pause) {
    if ($ok) { Start-Sleep -Seconds 4 } else { Read-Host 'Enter zum Schließen' | Out-Null }
}
