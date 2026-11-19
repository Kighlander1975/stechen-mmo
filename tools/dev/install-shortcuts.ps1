# Legt die Desktop-Verknüpfungen "Stechen-MMO starten" und "Stechen-MMO stoppen" an (erneut ausführbar).
$ErrorActionPreference = 'Stop'
$Pwsh = (Get-Command pwsh).Source
$Desktop = [Environment]::GetFolderPath('Desktop')
$Shell = New-Object -ComObject WScript.Shell

foreach ($entry in @(
    @{ Name = 'Stechen-MMO starten'; Script = 'start.ps1'; Icon = 'imageres.dll,101' },
    @{ Name = 'Stechen-MMO stoppen'; Script = 'stop.ps1'; Icon = 'imageres.dll,100' }
)) {
    $lnkPath = Join-Path $Desktop "$($entry.Name).lnk"
    $lnk = $Shell.CreateShortcut($lnkPath)
    $lnk.TargetPath = $Pwsh
    $lnk.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$(Join-Path $PSScriptRoot $entry.Script)`" -Pause"
    $lnk.WorkingDirectory = $PSScriptRoot
    $lnk.IconLocation = "$env:SystemRoot\System32\$($entry.Icon)"
    $lnk.Save()
    Write-Host "Angelegt: $lnkPath"
}
