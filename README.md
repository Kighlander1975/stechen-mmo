# stechen-mmo

stechen-mmo is an independent multiplayer card game project.

The repository is structured as a multi-component project with a Laravel web application and a planned Node.js game server (see `docs/HOSTING_UND_BETRIEB.md`).

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
├── homeserver/    # Reserved for the planned Node.js game server (empty)
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

### Node.js Game Server (planned)

The `homeserver/` directory is currently empty and reserved for the planned
Node.js game server. It will run the live game (GameEngine, WebSocket) while
Laravel stays authoritative for accounts, lobby, rooms and money. The former
optional HomeServer concept is no longer planned. Details:
`docs/HOSTING_UND_BETRIEB.md`.

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
- [Hosting, Betrieb und Spielserver](docs/HOSTING_UND_BETRIEB.md)

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

Today the lobby and room status are updated by polling through Laravel.

Target (decided 2026-11-19, not implemented): all updates in realtime through
the planned Node.js game server, which also runs the live game. Wallet and
ledger data stay exclusively in Laravel. Details:
`docs/HOSTING_UND_BETRIEB.md`.
