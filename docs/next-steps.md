# Next Steps

This document tracks the next small development steps.

The project will be built iteratively over several weeks. Large changes should be split into small, understandable commits.

## Current Status

- Repository exists on GitHub
- Main branch is used
- Initial root structure exists
- Official documentation folder exists
- Local private notes folder exists and is ignored by Git
- Development environment is documented
- Laravel is not installed yet
- HomeServer component is not implemented yet

## Immediate Next Steps

1. Review and adjust the official documentation structure
2. Prepare the Laravel application directory
3. Install Laravel into `laravel-app/`
4. Verify the Laravel application starts locally
5. Commit the clean Laravel base installation

## After Laravel Base Installation

1. Configure the database connection
2. Install or configure Inertia.js
3. Add Vue 3
4. Enable TypeScript
5. Configure Tailwind CSS
6. Verify Vite build
7. Commit each meaningful step separately

## Not Yet

The following topics are intentionally postponed:

- Game rules implementation
- Authentication
- Invite codes
- Lobby
- Realtime polling
- WebSocket HomeServer
- Deployment
- Admin tools

## Working Style

- Prefer small commits
- Keep documentation updated
- Keep Laravel and HomeServer clearly separated
- Keep `_docs/` for local scratch notes
- Move stable decisions from `_docs/` to `docs/`
