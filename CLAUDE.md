# CLAUDE.md

**READ FIRST EACH SESSION:** `docs/INDEX.md` (≤50 lines) + `docs/LLM_RULES.md`. Nothing else by default.

**Token economy rules: `docs/LLM_RULES.md`.** Lazy load. Grep > Read. Edit > rewrite. No sub-agents for <50 lines / <3 files.

## Stack

Flute CMS (gaming communities); PHP 8.2+; Symfony; Cycle ORM (ActiveRecord); Blade (`jenssegers/blade`).

## Where to look (load only the matching one)

- Architecture, container, bootstrap → `docs/HANDOVER_CORE_AI.md`
- Routes, middleware → `docs/HANDOVER_ROUTER_AI.md`
- DB, entities, migrations → `docs/HANDOVER_DB_AI.md`
- Blade, components, icons → `docs/HANDOVER_VIEWS_AI.md`
- Modules layout, list, deps → `docs/HANDOVER_MODULES_AI.md`
- Admin Platform/Screen → `docs/HANDOVER_ADMIN_AI.md`
- Payment gateways → `docs/HANDOVER_PAYMENTS_AI.md`
- Global helpers → `docs/HANDOVER_HELPERS_AI.md`
- Themes → `docs/HANDOVER_THEMES_AI.md`
- Events/listeners → `docs/HANDOVER_EVENTS_AI.md`
- "where is X?" → `docs/CODE_MAP.md`
- Human verbose Russian docs → `docs/01..21*.md` (do NOT auto-load)

## Conventions (non-negotiable)

- PSR-4: `Flute\` → `app/`; `Flute\Admin\` → `app/Core/Modules/Admin/`; `Flute\Modules\Name\` → `app/Modules/Name/`.
- Routes: `#[Route]` attribute only. No route files.
- DB: entities extend `Cycle\ActiveRecord\ActiveRecord`. Migrations only via `php flute generate:migration`.
- Views: `<x-icon path="ph.bold.scales-bold" />` for icons.
- Module APIs: call public Services/Contracts only; respect `module.json` deps.
- File size caps: controller/service/helper ≤400; view ≤300; >500 → split.

## Branches

`main` — stable; `early` — default dev.

## Git Safety

Assistants must not run `git commit` or `git push` without explicit user approval in the current conversation. Push approval is required every time.

## CLI

`php flute`: `template:clear`, `cache:clear`, `cache:warmup`, `logs:clear`, `logs:cleanup`, `generate:module`, `generate:migration`, `route:list`, `route:detail`, `cron:run`.

## After any task

Append 1–2 fact lines to the matching `docs/HANDOVER_*_AI.md` (format `KEY: value`). Never rewrite. Never copy. No "why" — only "what + where". If feature completed, 1 line to `docs/PROGRESS.md`.
