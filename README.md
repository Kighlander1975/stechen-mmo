# stechen-mmo

stechen-mmo is an independent multiplayer card game project.

The repository is structured as a multi-component project with a Laravel web application and a separate optional HomeServer component.

---

## Tech Stack

- Laravel 12
- PHP 8.5 (local development; `composer.json` requires `^8.2`)
- Blade views with Vue 3 islands (JavaScript)
- Tailwind CSS
- MySQL / MariaDB
- Vite
- PHPUnit
- Realtime: HTTP and adaptive polling

Not used: Inertia.js and TypeScript were named in early planning but are not
part of the implemented stack.

Open: a later realtime service (WebSocket) and the hosting target are still
under discussion.

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

The HomeServer directory is reserved for a later optional realtime component
and is currently empty:

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

Start here:

- [Current Project Status](docs/CURRENT_PROJECT_STATUS.md)
- [Reset Documentation Overview](docs/reset_dokumentation/00_PROJECT_STATUS.md)
- [Open Items](docs/reset_dokumentation/99_OFFENE_PUNKTE.md)
- [Game Rules](docs/GAME_RULES.md)
- [HomeServer Fallback Principle](docs/HOMESERVER_FALLBACK.md)

Historical documents live in `docs/archiv/` and are not authoritative.

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

`composer test` runs the same test suite.

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
