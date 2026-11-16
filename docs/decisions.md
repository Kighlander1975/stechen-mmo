# Project Decisions

This file tracks major project decisions.

## 2026-06-13: Repository Structure

Decision:

The repository root contains separate folders for the Laravel application and the HomeServer component.

```text
laravel-app/
homeserver/
```

Reason:

The Laravel application and the optional HomeServer/WebSocket service should stay clearly separated.

## 2026-06-13: Documentation Structure

Decision:

The repository contains two documentation folders:

```text
docs/
_docs/
```

- docs/ is tracked by Git and contains official project documentation.
- _docs/ is ignored by Git and contains local notes, temporary ideas and daily planning.

Reason:

Official documentation should be clean and shareable, while local notes can remain informal and private.

## 2026-06-13: Tech Stack Baseline

Decision:

The accepted baseline tech stack is:

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
- Polling-first realtime
- Optional later: Node.js WebSocket service

Reason:

The stack should be modern, suitable for Laravel development, useful as a portfolio reference and compatible with the planned hosting environment.

Status: partly superseded, see 2026-11-16.

## 2026-11-16: Frontend Stack Aligned With Implementation

Decision:

The frontend is built with Blade views and Vue 3 islands in JavaScript.
Inertia.js and TypeScript from the 2026-06-13 baseline are not part of the
implemented stack. Vitest is not set up.

Reason:

The implementation uses the Blade and Vue-island architecture
(`docs/FRONTEND_VUE_ISLANDS.md`); the code contains no Inertia.js and no
TypeScript. This entry records the actual state so
the baseline above is no longer read as current.
