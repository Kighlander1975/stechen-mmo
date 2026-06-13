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
- Inertia.js should stay on Inertia 2.x
- TypeScript should stay on TypeScript 5.x

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

- Vue 3.x
- TypeScript 5.x
- Inertia.js 2.x
- Vite
- Tailwind CSS
- npm 11.x

### Realtime Strategy

Phase 1:

- Adaptive polling through Laravel endpoints

Phase 2:

- Optional Node.js WebSocket service
- HomeServer component may be introduced later
- Laravel remains authoritative for game state

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
- Web frontend through Inertia/Vue
- Polling endpoints
- Main application entry point

### HomeServer

Location:

```text
homeserver/
```

Purpose:

- Optional future realtime service
- Node.js/WebSocket service
- Not authoritative for game state
- Polling fallback remains available
- May be added after the Laravel polling implementation works

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
