# Tech Stack

This document describes the planned technology stack for the project.

The project is developed as a personal learning, portfolio, and showcase project. It targets the author's local development environment and intentionally uses modern technology versions that were available and established by the end of 2025.

Broad backwards compatibility is not a primary goal.

## Version Strategy

The project should stay within known and documented major versions.

Newer local runtimes may be used if they remain compatible, but project dependencies should not accidentally jump to newer major versions without an explicit decision.

Examples:

- Laravel should stay on Laravel 12.x
- Vue should stay on Vue 3.x

Major version upgrades should be documented before they are applied.

## Local Development Environment

Initial local environment:

```text
PHP       8.5.4
Composer  2.9.5
Node.js   24.11.0
npm       11.7.0
Git       2.45.1.windows.1
```

## Accepted Baseline

### Backend

- PHP 8.5.x
- Composer 2.9.x
- Laravel 12.x
- MySQL or MariaDB
- PHPUnit

### Frontend

- Blade views with Vue 3.x islands (JavaScript)
- Vite
- Tailwind CSS
- npm 11.x

Inertia.js and TypeScript were part of the initial plan but were not
adopted. See `docs/decisions.md` (2026-11-16).

### Realtime Strategy

Updated 2026-11-19 (see `docs/HOSTING_UND_BETRIEB.md`):

- Today: lobby and status use polling through Laravel endpoints
  (implemented).
- Target: all updates in realtime through the Node.js game server, including
  lobby and status (decided, not implemented). Whether polling stays as an
  emergency fallback is open.
- Live game: Node.js game server with WebSocket (decided, not implemented).
- Laravel remains authoritative for accounts, lobby, rooms and money; the
  Node.js game server is authoritative for the live game state.
- The former HomeServer component is no longer planned.

## Components

### Laravel Application

Location:

```text
laravel-app/
```

Purpose:

- Authoritative backend
- Authentication
- Game logic
- Database persistence
- Web frontend through Blade views and Vue islands
- Polling endpoints
- Main application entry point

### Node.js Game Server (planned)

Location: currently reserved as `homeserver/` (empty). Renaming the folder is
an open decision.

Purpose:

- Runs the live game (GameEngine) and its realtime communication
- Persists every game action as an event for crash recovery
- Never moves money; hands the result back to Laravel

Status: decided, not implemented. See `docs/HOSTING_UND_BETRIEB.md`.

## Installation Rules

Laravel should be installed explicitly as Laravel 12:

```text
composer create-project laravel/laravel:^12.0 laravel-app
```

The project should not be initialized with an unconstrained Laravel version such as:

```text
composer create-project laravel/laravel laravel-app
```

unless a future major version upgrade has been documented first.

## License

The license decision is postponed.

Until a license is explicitly added, the project should be treated as a personal project with no granted reuse rights.
