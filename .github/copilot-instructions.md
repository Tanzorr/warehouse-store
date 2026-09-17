# Shop Warehouse — Monorepo Guidelines

## Project Structure

This is a monorepo with three independent apps:

| Folder | Framework | Purpose |
|--------|-----------|---------|
| `shop-2-back/` | Laravel 11 + PHP 8.2 | Shop REST API (Sanctum auth, Swagger docs) |
| `shop-front/` | Angular 20 + NgRx 19 | Single-page frontend |
| `warehouses/` | Symfony 7.2 + API Platform 4 | Warehouse microservice (JWT auth) |

## General Rules

- All code, comments, variable names, and commit messages must be in **English**
- Use conventional commits: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`
- Never decrease existing static analysis levels (PHPStan level 4, Psalm errorLevel 3)
- Do not add dependencies without discussing trade-offs
- Each app has its own `composer.json` / `package.json` — do not mix them

## Code Style

- No dead code, no unused imports, no commented-out blocks
- Prefer explicit types over `mixed` or `any`
- Keep functions/methods short and single-purpose
- Follow existing folder structure — do not introduce new top-level directories without reason
