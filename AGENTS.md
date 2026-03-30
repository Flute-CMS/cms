# Repository Guidelines

## Structure

- Source: `app/Core`, `app/Modules/*`, `app/Themes/*`, `app/Helpers`.
- Web: `public/` (`index.php`). Config: `config/`, local `config-dev/` (no secrets). Runtime: `bootstrap/`, `storage/`. i18n: `i18n/`. Docker/nginx presets at repo root.
- PSR-4: `Flute\` → `app/`; Composer merges `app/Modules/*/composer.json`.

## Commands

- `composer install` — deps.
- `composer test` — Mago lint + format check (CI).
- `composer fix` / `composer test-fix` / `composer format` — auto-fix; `composer lint`, `composer format-check`, `composer analyse` — lint / dry-run / analyze.
- `php flute cache:clear`, `template:cache:clear`, `logs:clear` — cache/logs. More: `CLAUDE.md`.

## Style & naming

PHP 8.2, PSR-12; 4 spaces, short arrays; imports sorted, unused removed; Mago (`mago.toml`). Namespaces `Flute\...`; modules `app/Modules/Vendor/Feature` (StudlyCase). One class per file; no `.class.php`. Routes: `#[Route]` only, no separate route files.

## Tests

Add under `tests/`, `Flute\Tests\`; `SomethingTest.php`; deterministic. For now `composer test` is the main gate.

## Commits & PRs

Imperative scoped commits, e.g. `core: fix router cache` or `module:Shop add refund hook`. PRs: purpose, issues, verify steps, `composer test` green; do not commit generated junk under `public/`.

## Security

No secrets in repo; use `config-dev/`. `storage/` writable; only `public/` web-exposed.
