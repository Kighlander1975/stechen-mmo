# Tech Stack

## Accepted Baseline

- Laravel 12
- PHP 8.5
- Vue 3
- TypeScript
- Inertia.js
- Tailwind CSS
- MySQL / MariaDB
- Vite
- PHPUnit
- Optional later: Vitest
- Realtime phase 1: adaptive polling
- Realtime phase 2: optional Node.js WebSocket service

## Components

### Laravel Application

Location:

`	ext
laravel-app/
`

Purpose:

- Authoritative backend
- Authentication
- Game logic
- Database persistence
- Web frontend through Inertia/Vue
- Polling endpoints

### HomeServer

Location:

`	ext
homeserver/
`

Purpose:

- Optional future realtime service
- Node.js/WebSocket service
- Not authoritative for game state
- Polling fallback remains available
