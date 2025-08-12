# Repository Guidelines

## Project Structure & Module Organization
- Source: `app/Core` (kernel, DI, console), `app/Modules/*` (feature modules), `app/Themes/*` (themes), `app/Helpers`.
- Web root: `public/` (assets, entry files); front controller: `index.php`.
- Config: `config/` (default), `config-dev/` (local overrides, do not commit secrets).
- Bootstrap/runtime: `bootstrap/`, writable data in `storage/`.
- Localization: `i18n/`; Nginx/docker presets in `nginx/`, `docker-compose.yml`.
- Autoloading: PSR-4 (`Flute\` → `app/`). Composer merges module `composer.json` files from `app/Modules/*`.

## Build, Test, and Development Commands
- `composer install`: install dependencies.
- `composer test`: style check via php-cs-fixer (acts as CI “test”).
- `composer test-fix` or `composer format`: auto-fix coding style.
- `composer format-check`: dry-run style check.
- `composer analyse`: prints a notice; PHPStan is temporarily disabled.
- `php flute cache:clear` | `php flute template:cache:clear` | `php flute logs:clear`: project CLI utilities.
- Run locally: via Docker (`docker-compose up -d`) or your PHP/Nginx pointing to `public/`.

## Coding Style & Naming Conventions
- PHP 8.2, PSR-12; 4-space indentation, short arrays, ordered and unused import rules enforced by php-cs-fixer (`.php-cs-fixer.php`).
- Namespaces: `Flute\...`; modules live in `app/Modules/VendorName/Feature` using StudlyCase; services and components reside under `app/Core/*`.
- Filenames: one class per file; avoid suffixes like `.class.php`.

## Testing Guidelines
- Current repo uses style checks as the “test” phase. Add tests under `tests/` with namespace `Flute\Tests\` when needed.
- Suggested naming: `SomethingTest.php`; keep tests deterministic and fast. Run with `composer test` for now.

## Commit & Pull Request Guidelines
- Commits: imperative, scoped messages. Example: `core: fix router cache invalidation` or `module:Shop add refund hook`.
- Pull Requests: include purpose, linked issues, before/after notes or screenshots, and steps to verify. Ensure `composer test` passes and no new files in `public/` are generated during builds.

## Security & Configuration Tips
- Never commit secrets; prefer `config-dev/` for local overrides. Ensure `storage/` is writable; keep `public/` as the only web-exposed directory.
