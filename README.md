# stechen-mmo

stechen-mmo is an independent multiplayer card game project.

The repository is structured as a multi-component project with a Laravel web application and a separate optional HomeServer component.

---

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

---

## Repository Structure

```text
stechen-mmo/
├── laravel-app/   # Laravel web application
├── homeserver/    # Optional Node.js/WebSocket HomeServer component
├── docs/          # Official project documentation, tracked by Git
├── _docs/         # Local/private notes, ignored by Git
├── README.md
├── .editorconfig
├── .gitattributes
└── .gitignore
```

---

## Components

### Laravel App

The Laravel application lives in:

```text
laravel-app/
```

It contains the main application, including:

- web routes
- Blade views
- Vue 3 frontend islands
- Vite build pipeline
- database migrations
- application services
- tests

### HomeServer

The HomeServer directory is reserved for a later optional realtime component:

```text
homeserver/
```

The MVP must not depend on this component being available.

Laravel remains the authoritative application. HTTP and polling are the required fallback mechanism.

---

## Documentation

Official documentation lives in:

```text
docs/
```

Current project documentation:

- [Phase 1 Foundation](docs/PHASE_1_FOUNDATION.md)
- [HomeServer Fallback Principle](docs/HOMESERVER_FALLBACK.md)

Temporary local notes live in:

```text
_docs/
```

The `_docs` directory is intentionally excluded from Git.

---

## Development

Run Laravel commands from:

```text
laravel-app/
```

Example:

```powershell
cd laravel-app
php artisan test
```

At the moment, the valid test command is:

```text
php artisan test
```

A Composer test script is currently not defined.

---

## Realtime Principle

For the MVP:

```text
HTTP first.
Polling is allowed.
Realtime is optional.
Laravel is authoritative.
```

A later HomeServer or WebSocket service may improve update delivery, but must not become the sole source of truth for game state or wallet data.
