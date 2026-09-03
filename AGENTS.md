# Repository Guidelines

**Always read first:** `docs/INDEX.md` + `docs/LLM_RULES.md`. Per-domain context in `docs/HANDOVER_*_AI.md`. Locate files via `docs/CODE_MAP.md`.

**Token economy rules: `docs/LLM_RULES.md` (canonical).** Grep before Read. Edit (oldString/newString) — never full-file rewrites. Independent ops → parallel tool calls in one turn. Sub-agents only when >5 files or cross-domain.

## Structure

- Source: `app/Core`, `app/Modules/*`, `app/Themes/*`, `app/Helpers`.
- Web: `public/` (`index.php`). Config: `config/`, local `config-dev/` (no secrets). Runtime: `bootstrap/`, `storage/`. i18n: `i18n/`. Docker/nginx presets at repo root.
- PSR-4: `Flute\` → `app/`; Composer merges `app/Modules/*/composer.json`.

## Commands

- `composer install` — deps.
- `composer test` — Mago lint + format check (CI gate).
- `composer fix` / `composer test-fix` / `composer format` — auto-fix; `composer lint`, `composer format-check`, `composer analyse` — lint / dry-run / analyze.
- `php flute cache:clear`, `template:cache:clear`, `logs:clear` — cache/logs. More: `CLAUDE.md`.

## Style & naming

PHP 8.2, PSR-12; 4 spaces, short arrays; imports sorted, unused removed; Mago (`mago.toml`). Namespaces `Flute\...`; modules `app/Modules/Vendor/Feature` (StudlyCase). One class per file; no `.class.php`. Routes: `#[Route]` only, no separate route files. File size: controller/service ≤400 lines; >500 → split.

## Tests

Add under `tests/`, `Flute\Tests\`; `SomethingTest.php`; deterministic. Gate: `composer test`.

## Commits & PRs

Imperative scoped commits, e.g. `core: fix router cache` or `module:Shop add refund hook`. PRs: purpose, issues, verify steps, `composer test` green; do not commit generated junk under `public/`.

Assistants must not run `git commit` or `git push` on their own. Stop and ask for explicit approval before each commit/push. Pushes must always be approved by the user in the current conversation.

## Security

No secrets in repo; use `config-dev/`. `storage/` writable; only `public/` web-exposed.

## After task

Append 1–2 fact lines to the matching `docs/HANDOVER_*_AI.md`. No prose. No rewrite. Completed feature → 1 line to `docs/PROGRESS.md`.
