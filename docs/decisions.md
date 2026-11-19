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

## 2026-11-19: Hosting, Domains, Deployment and Game Server Architecture

Decision (details in German: `docs/HOSTING_UND_BETRIEB.md`):

- Hosting moves to a Hetzner server once a beta makes sense; until then all
  development is local. ALL-INKL is no longer a hosting target and only
  provides the domains (main domain plus `beta.<main domain>` for staging).
- Two separate environments on the server: production and staging.
- Deployments may be automated, also by the AI assistant: staging with one
  approval per deploy round, production with explicit approval per deploy.
  No credentials in documentation or repository.
- Game server architecture "variant B": a Node.js game server runs the live
  game (GameEngine, WebSocket, persisted event log). Laravel handles
  everything before the start and after the end, and is the only component
  that moves money. Handover in both directions is signed and idempotent.
  Implementation starts only after the surrounding platform works reliably.
- Real money is on hold; play money has no monetary value.

Reason:

The project owner gained access to a Hetzner server. A dedicated server for
the live game makes the shared-hosting and HomeServer fallback concept
(`docs/Stechen-Serverkonzept.md`, `docs/HOMESERVER_FALLBACK.md`) unnecessary,
and running the game in Node.js fits the realtime nature of a card game.

Supersedes: the HomeServer role in "2026-06-13: Repository Structure" and the
realtime line "Optional later: Node.js WebSocket service" in the 2026-06-13
baseline.
