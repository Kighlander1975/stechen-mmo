# Development Environment

This document describes the local development environment used for the initial setup of the project.

## Local System

Initial environment checked on:

`	ext
2026-06-13
`

## Installed Tool Versions

`	ext
PHP       8.5.4
Composer  2.9.5
Node.js   24.11.0
npm       11.7.0
Git       2.45.1.windows.1
`

## PHP

`	ext
PHP 8.5.4 (cli)
Zend Engine v4.5.4
Zend OPcache v8.5.4
`

Composer uses:

`	ext
C:\php\php.exe
`

## Required Tools

The project is planned to require the following tools for local development:

- PHP 8.5
- Composer
- Node.js
- npm
- Git
- MySQL or MariaDB

## Planned Project Components

### Laravel Application

Location:

`	ext
laravel-app/
`

Requires:

- PHP 8.5
- Composer
- MySQL / MariaDB
- Node.js / npm for frontend asset builds

### HomeServer Component

Location:

`	ext
homeserver/
`

Requires later:

- Node.js
- npm
- Optional WebSocket-related packages

## Lokaler Start und Stopp

Stand: 2026-11-19. Die Abschnitte oben beschreiben den Einrichtungsstand vom
2026-06-13 und sind historisch.

Der lokale Entwicklungsbetrieb läuft unabhängig von VS Code unsichtbar im
Hintergrund:

```powershell
pwsh -File tools\dev\start.ps1   # startet Webserver, Scheduler und Vite
pwsh -File tools\dev\stop.ps1    # stoppt alles und entfernt public/hot
```

- Gestartet werden `php artisan serve` (`127.0.0.1:8000`),
  `php artisan schedule:work` und Vite, jeweils mit versteckter Konsole.
  PIDs und Startzeiten stehen in `laravel-app/storage/logs/dev-processes.json`;
  `stop.ps1` beendet nur diese Prozesse samt Unterprozessen.
- PM2 wurde verworfen: Sein Hintergrunddienst hat keine Konsole, deshalb öffnen
  die `cmd`-Unterprozesse von `artisan serve` und `schedule:work` unter Windows
  sichtbare Fenster.
- Die App ist unter `http://127.0.0.1:8000` erreichbar. `localhost:8000` ist
  auf dem Entwicklungsrechner unzuverlässig, weil dort eine andere Anwendung
  IPv6 `::1:8000` belegt.
- `start.ps1` prüft vorher den Windows-Dienst `MariaDB` und ob Port 8000 frei
  ist, und wartet auf eine HTTP-Antwort.
- Logs: `laravel-app/storage/logs/dev-<web|scheduler|vite>.<out|err>.log`.
  Sie werden bei jedem Start überschrieben.
- `tools/dev/install-shortcuts.ps1` legt die Desktop-Verknüpfungen
  „Stechen-MMO starten“ und „Stechen-MMO stoppen“ an.
- Die Raumversorgung (`php artisan game-rooms:supply`) läuft derzeit nicht über
  den Scheduler und muss bei Bedarf manuell ausgeführt werden.
