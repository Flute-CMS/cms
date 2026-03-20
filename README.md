<p align="center">
  <img src="https://github.com/user-attachments/assets/f14b1c21-b3b0-470e-b0df-f1cc05b3cab7" alt="Flute CMS" width="100%">
</p>

<h3 align="center">Open-source CMS for game server communities</h3>

<p align="center">
  <a href="https://github.com/flute-cms/cms/releases"><img src="https://img.shields.io/github/v/release/flute-cms/cms?style=flat-square&color=blue" alt="Release"></a>
  <a href="https://github.com/flute-cms/cms/blob/early/LICENSE"><img src="https://img.shields.io/github/license/flute-cms/cms?style=flat-square" alt="License"></a>
  <a href="https://github.com/flute-cms/cms/stargazers"><img src="https://img.shields.io/github/stars/flute-cms/cms?style=flat-square" alt="Stars"></a>
  <a href="https://discord.gg/BcBMeVJJsd"><img src="https://img.shields.io/discord/869991184968323103?style=flat-square&label=discord&color=5865F2" alt="Discord"></a>
  <img src="https://img.shields.io/badge/php-≥8.2-8892BF?style=flat-square" alt="PHP 8.2+">
</p>

<p align="center">
  <a href="https://docs.flute-cms.com">Documentation</a> · <a href="https://flute-cms.com">Website</a> · <a href="https://discord.gg/BcBMeVJJsd">Discord</a> · <a href="https://flute-cms.com/marketplace">Marketplace</a> · <a href="README.ru.md">Русский</a>
</p>

---

**Flute** is a free, self-hosted CMS designed for game server communities — CS2, CS:GO, TF2, Minecraft, and others. It ships with a modular plugin system, a built-in marketplace, an admin panel, payment integrations, and social auth out of the box. Install it, extend it, make it yours.

> [!NOTE]
> Flute is under active development on the `early` branch. The `main` branch contains stable releases.
> If you'd like to contribute, please see the [Contributing](#contributing) section.

## Features

- **Modular architecture** — install modules and themes from the built-in marketplace or develop your own
- **Game server integration** — native support for Source (CS2, CS:GO, TF2), GoldSrc (CS 1.6), and Minecraft servers via query protocols
- **Payment system** — built-in shop with 15+ payment gateways through Omnipay (Stripe, PayPal, FreeKassa, and more)
- **Admin panel** — full management interface with role-based access control, analytics, and one-click updates
- **Social authentication** — OAuth2 login via Steam, Discord, VK, Google, GitHub, and others through HybridAuth
- **REST API** — first-class API for external integrations and custom frontends
- **Theme engine** — Blade templating with SCSS compilation (native dart-sass), theme inheritance, and live preview
- **Multilingual** — complete i18n system with runtime language switching
- **Player stats & bans** — integrations with Levels Ranks, IKS Admin, Admin System, and more
- **Caching** — multi-driver cache layer (Redis, Memcached, APCu, filesystem) with stale-while-revalidate support

## Getting Started

### Requirements

- PHP 8.2+ (with `ext-json`, `ext-mbstring`, `ext-pdo`)
- MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 12+
- Composer 2.x

### Installation

```bash
git clone https://github.com/flute-cms/cms.git
cd cms
composer install
```

Open your browser and follow the web installer. That's it.

> [!TIP]
> Docker Compose and Nginx presets are included in the repository. See the [deployment guide](https://docs.flute-cms.com/en/guides/install) for details.

## Tech Stack

Flute is built on PHP 8.2+ with a component-based architecture:

- **Routing & HTTP** — Symfony Routing, HttpFoundation, HttpKernel
- **Database** — [Cycle ORM](https://cycle-orm.dev) with Active Record and auto-migrations
- **Templates** — [Blade](https://github.com/jenssegers/blade) (Illuminate View) with SCSS via dart-sass / scssphp
- **Authentication** — [HybridAuth](https://hybridauth.github.io/) for OAuth2 providers
- **Payments** — [Omnipay](https://omnipay.thephpleague.com/) for payment gateway abstraction
- **Caching** — Symfony Cache (Redis, Memcached, APCu, filesystem adapters)
- **Logging** — Monolog
- **DI Container** — PHP-DI
- **HTTP Client** — Guzzle

## Project Structure

```
app/
├── Core/                   # Kernel: DI container, router, services, console
│   ├── Database/Entities/  # Cycle ORM entities
│   ├── Modules/            # Built-in modules (Admin, Auth, Payments, …)
│   ├── Services/           # Core services (Cache, Email, Encrypt, …)
│   └── ServiceProviders/   # DI service providers
├── Modules/                # Community / custom modules
├── Themes/                 # Installable themes
└── Helpers/                # Global helpers

config/                     # Default configuration
config-dev/                 # Local overrides (git-ignored)
public/                     # Web root — the only exposed directory
storage/                    # Cache, logs, uploads (writable)
i18n/                       # Localizations
```

## Module Development

Every module lives in `app/Modules/<Name>/` with a consistent structure:

```
MyModule/
├── module.json             # Metadata, version, dependencies
├── Providers/              # Service providers
├── Controllers/            # Route-attributed controllers
├── Services/               # Business logic
├── Resources/
│   ├── views/              # Blade templates
│   └── lang/               # Translations
└── database/migrations/    # Auto-generated migrations
```

Routes use PHP attributes — no separate route files:

```php
#[Route('/my-module/example', name: 'my-module.example')]
public function example(): Response
{
    return view('MyModule::pages.example');
}
```

Full guide → [docs.flute-cms.com/en/modules](https://docs.flute-cms.com/en/modules)

## CLI

```bash
php flute cache:clear              # Clear application cache
php flute cache:warmup             # Warm up cache
php flute template:cache:clear     # Clear compiled templates
php flute generate:migration       # Generate DB migration from entities
php flute generate:module          # Scaffold a new module
php flute routes:list              # Print all registered routes
php flute cron:run                 # Execute scheduled tasks
```

## Contributing

We welcome contributions — bug reports, features, code, docs, and translations.

1. Fork the repo and create a branch from `early`
2. Make your changes and ensure `composer test` passes
3. Submit a pull request

**Branches:**
- `main` — stable releases
- `early` — active development (target your PRs here)

**Commit convention:** imperative, scoped messages:
```
feat(auth): add OAuth2 authentication
fix(database): resolve connection timeout
core: fix router cache invalidation
module:Shop: add refund hook
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full guide.

## Security

If you discover a security vulnerability, please email [flamesworkk@gmail.com](mailto:flamesworkk@gmail.com) instead of using the issue tracker. All security issues will be addressed promptly.

## Community

- [Discord](https://discord.gg/BcBMeVJJsd) — live chat and support
- [GitHub Issues](https://github.com/flute-cms/cms/issues) — bug reports and feature requests
- [Documentation](https://docs.flute-cms.com) — guides and API reference

## License

Flute is open-source software licensed under the [GNU General Public License v3.0 or later](LICENSE).

---

<p align="center">Created by <a href="https://github.com/FlamesONE">Flames</a></p>
