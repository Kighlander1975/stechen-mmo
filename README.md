# stechen-mmo

stechen-mmo is an independent multiplayer card game project.

The repository is structured as a multi-component project with a Laravel web application and a separate HomeServer component.

## Planned Tech Stack

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

## Repository Structure

`	ext
stechen-mmo/
├── laravel-app/   # Laravel web application
├── homeserver/    # Optional Node.js/WebSocket HomeServer component
├── docs/          # Official project documentation, tracked by Git
├── _docs/         # Local/private notes, ignored by Git
├── README.md
└── .gitignore
`

## Documentation

Official documentation lives in:

`	ext
docs/
`

Temporary local notes live in:

`	ext
_docs/
`

The _docs directory is intentionally excluded from Git.
