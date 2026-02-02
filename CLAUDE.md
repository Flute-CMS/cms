# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Flute CMS is a modular content management system for gaming communities (CS2, CS:GO, Minecraft, etc.). Built on PHP 8.2+ using Symfony components and Cycle ORM.

## Build, Test, and Development Commands

```bash
# Install dependencies
composer install

# Code style check (CI "test")
composer test

# Auto-fix code style
composer test-fix # or composer format

# Dry-run style check
composer format-check

# Project CLI utilities
php flute cache:clear # Clear cache
php flute template:cache:clear # Clear template cache
php flute logs:clear # Clear logs
php flute logs:cleanup # Remove old logs
php flute cache:warmup # Warm up cache
php flute generate:migration # Generate migration
php flute generate:module # Generate module
php flute routes:list # List routes
php flute route:detail # Route details
php flute cron:run # Run cron jobs
```

## Architecture

### Directory Structure

* `app/Core/` - Core: App.php (main class), DI container, console, router, services
* `app/Core/Modules/` - Built-in modules (Admin, Auth, Home, Payments, Profile, etc.)
* `app/Core/Services/` - Core services (User, Session, Cache, Email, Encrypt, etc.)
* `app/Core/ServiceProviders/` - Service providers for the DI container
* `app/Core/Database/Entities/` - Core Cycle ORM entities
* `app/Modules/` - Custom / additional modules
* `app/Themes/` - UI themes
* `app/Helpers/` - Helpers
* `config/` - Default configuration
* `config-dev/` - Local overrides (do not commit)
* `bootstrap/` - Application bootstrap (app.php)
* `storage/` - Writable data (cache, logs, uploads)
* `public/` - Web root (the only public directory)
* `i18n/` - Localizations

### Key Components

**App** (`app/Core/App.php`):
Main application class. Uses traits for container, router, theme, language, and logger. Singleton.

**Router**:
Symfony Routing with attribute-based annotations. Routes are defined using `#[Route]` attributes on controller methods.
Middlewares are registered in `app/Core/Router/Router.php` (e.g., `auth`, `admin`, `csrf`).

**Database**:
Cycle ORM with Active Record. Entities are located in `app/Core/Database/Entities/`.
* **Auto-migration**: Migrations are generated automatically from Entity attributes via `php flute generate:migration`.
* **ActiveRecord**: Entities MUST extend `Cycle\ActiveRecord\ActiveRecord` to use methods like `query()`, `save()`, `delete()`.

**Template**:
Blade templating engine (jenssegers/blade).
Use the `<x-icon>` component for icons (example: `<x-icon path="ph.bold.scales-bold" />`, `<x-icon path="ph.regular.scales" />`).

**Modules**:
Each module in `app/Modules/ModuleName/` contains:

* `module.json` - metadata, version, providers, dependencies
* `Providers/` - module service providers
* `Controllers/` - controllers with route attributes
* `Resources/views/` - Blade templates
* `Resources/lang/` - translations
* `database/migrations/` - module migrations
* `Services/` - module services

### Autoloading (PSR-4)

* `Flute\` → `app/`
* `Flute\Admin\` → `app/Core/Modules/Admin/`
* `Flute\Modules\ModuleName\` → `app/Modules/ModuleName/`

## Module & Admin Development

### Modules
* **Function Safety**: Verify existence of methods/classes when interacting with other modules.
* **Dependencies**: Respect module dependencies in `module.json`.

### Admin Screens
Structure (`Flute\Admin\Platform\Screen`):
* **Properties**: `$name`, `$description`, `$permission`.
* **Lifecycle**: `mount()` (init data), `commandBar()` (actions), `layout()` (UI structure).
* **Layouts**: Use `LayoutFactory` for components (Fields, Tabs, Tables, Modals).
* **Actions**: Public methods handle events (e.g., `saveUser()`).
* **Helpers**: `rep()` (Repository), `user()->can()` (Permissions), `breadcrumb()`, `auth()`, `router()`.

## Coding Standards

* PHP 8.2+, PSR-12
* 4 spaces for indentation, short array syntax
* Imports are sorted alphabetically, unused ones removed
* php-cs-fixer is applied to `app/Core` and `app/Modules` (excluding Entities)
* Routes use `#[Route]` attributes, no separate route files
* One class per file, no `.class.php` suffix

## Commit Convention

Imperative, scoped messages:

* `feat(auth): add OAuth2 authentication`
* `fix(database): resolve connection timeout`
* `core: fix router cache invalidation`
* `module:Shop: add refund hook`

## Branches

* `main` - stable releases
* `early` - development branch (default)
