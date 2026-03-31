# CLAUDE.md

Claude Code in this repo: **commands, layout, style, tests, commits, PRs, security → `AGENTS.md`.**

## Stack

Flute CMS (gaming communities); PHP 8.2+; Symfony; Cycle ORM; Blade (`jenssegers/blade`).

## Architecture (Flute-specific)

- **App** (`app/Core/App.php`): singleton; traits for container, router, theme, language, logger.
- **Router**: `#[Route]` on controllers; middlewares in `app/Core/Router/Router.php` (`auth`, `admin`, `csrf`, …).
- **DB**: Cycle Active Record; entities in `app/Core/Database/Entities/`; extend `Cycle\ActiveRecord\ActiveRecord`; `php flute generate:migration`.
- **Views**: `<x-icon path="ph.bold.scales-bold" />` (Phosphor icon paths).
- **Module** (`app/Modules/Name/`): `module.json`, `Providers/`, `Controllers/`, `Resources/views`, `Resources/lang/`, `database/migrations/`, `Services/`.
- **PSR-4**: `Flute\` → `app/`; `Flute\Admin\` → `app/Core/Modules/Admin/`; `Flute\Modules\Name\` → `app/Modules/Name/`.

## Admin (`Flute\Admin\Platform\Screen`)

`$name`, `$description`, `$permission`; `mount()`, `commandBar()`, `layout()` via `LayoutFactory` (Fields, Tabs, Tables, Modals); public methods for actions; helpers `rep()`, `user()->can()`, `breadcrumb()`, `auth()`, `router()`.

## Modules

Check APIs when calling other modules; respect `module.json` dependencies.

## Branches

`main` — stable; `early` — default development.

## CLI beyond AGENTS.md

`php flute`: `template:cache:clear`, `logs:clear`, `logs:cleanup`, `cache:warmup`, `generate:module`, `routes:list`, `route:detail`, `cron:run`.
