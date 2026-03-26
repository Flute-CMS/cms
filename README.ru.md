<p align="center">
  <img src="https://github.com/user-attachments/assets/f14b1c21-b3b0-470e-b0df-f1cc05b3cab7" alt="Flute CMS" width="100%">
</p>

<h3 align="center">Open-source CMS для игровых сообществ</h3>

<p align="center">
  <a href="https://github.com/flute-cms/cms/releases"><img src="https://img.shields.io/github/v/release/flute-cms/cms?style=flat-square&color=blue" alt="Релиз"></a>
  <a href="https://github.com/flute-cms/cms/blob/early/LICENSE"><img src="https://img.shields.io/github/license/flute-cms/cms?style=flat-square" alt="Лицензия"></a>
  <a href="https://github.com/flute-cms/cms/stargazers"><img src="https://img.shields.io/github/stars/flute-cms/cms?style=flat-square" alt="Звёзды"></a>
  <a href="https://discord.gg/BcBMeVJJsd"><img src="https://img.shields.io/discord/869991184968323103?style=flat-square&label=discord&color=5865F2" alt="Discord"></a>
  <img src="https://img.shields.io/badge/php-≥8.2-8892BF?style=flat-square" alt="PHP 8.2+">
</p>

<p align="center">
  <a href="https://docs.flute-cms.com">Документация</a> · <a href="https://flute-cms.com">Сайт</a> · <a href="https://discord.gg/BcBMeVJJsd">Discord</a> · <a href="https://flute-cms.com/marketplace">Маркетплейс</a> · <a href="README.md">English</a>
</p>

---

**Flute** — бесплатная, self-hosted CMS для игровых сообществ: CS2, CS:GO, TF2, Minecraft и других. Из коробки — модульная система с маркетплейсом, админ-панель, интеграция платежей и социальная авторизация. Установите, расширьте, сделайте своей.

> [!NOTE]
> Flute активно разрабатывается в ветке `early`. Ветка `main` содержит стабильные релизы.
> Если хотите помочь с разработкой, смотрите раздел [Участие в разработке](#участие-в-разработке).

## Возможности

- **Модульная архитектура** — установка модулей и тем из встроенного маркетплейса или разработка собственных
- **Интеграция с игровыми серверами** — нативная поддержка Source (CS2, CS:GO, TF2), GoldSrc (CS 1.6) и Minecraft через query-протоколы
- **Платёжная система** — встроенный магазин с 15+ платёжными шлюзами через Omnipay (Stripe, PayPal, FreeKassa и другие)
- **Админ-панель** — полноценный интерфейс управления с ролевой моделью доступа, аналитикой и обновлениями в один клик
- **Социальная авторизация** — OAuth2 через Steam, Discord, VK, Google, GitHub и другие через HybridAuth
- **REST API** — полноценный API для внешних интеграций и кастомных фронтендов
- **Система тем** — Blade-шаблоны с компиляцией SCSS (нативный dart-sass), наследованием тем и превью
- **Мультиязычность** — полная система i18n с переключением языков на лету
- **Статистика и баны** — интеграции с Levels Ranks, IKS Admin, Admin System и другими
- **Кэширование** — мультидрайверный кэш (Redis, Memcached, APCu, файловая система) с поддержкой stale-while-revalidate

## Быстрый старт

### Требования

- PHP 8.2+ (с расширениями `ext-json`, `ext-mbstring`, `ext-pdo`)
- MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 12+
- Composer 2.x

### Установка

```bash
git clone https://github.com/flute-cms/cms.git
cd cms
composer install
```

Откройте браузер и следуйте веб-установщику. Всё.

> [!TIP]
> Docker Compose и пресеты Nginx включены в репозиторий. Подробнее в [руководстве по развёртыванию](https://docs.flute-cms.com/en/guides/install).

## Технологический стек

Flute построена на PHP 8.2+ с компонентной архитектурой:

- **Роутинг и HTTP** — Symfony Routing, HttpFoundation, HttpKernel
- **База данных** — [Cycle ORM](https://cycle-orm.dev) с Active Record и автомиграциями
- **Шаблоны** — [Blade](https://github.com/jenssegers/blade) (Illuminate View) с SCSS через dart-sass / scssphp
- **Авторизация** — [HybridAuth](https://hybridauth.github.io/) для OAuth2-провайдеров
- **Платежи** — [Omnipay](https://omnipay.thephpleague.com/) для абстракции платёжных шлюзов
- **Кэш** — Symfony Cache (Redis, Memcached, APCu, файловые адаптеры)
- **Логирование** — Monolog
- **DI-контейнер** — PHP-DI
- **HTTP-клиент** — Guzzle

## Структура проекта

```
app/
├── Core/                   # Ядро: DI-контейнер, роутер, сервисы, консоль
│   ├── Database/Entities/  # Сущности Cycle ORM
│   ├── Modules/            # Встроенные модули (Admin, Auth, Payments, …)
│   ├── Services/           # Сервисы ядра (Cache, Email, Encrypt, …)
│   └── ServiceProviders/   # DI-провайдеры
├── Modules/                # Пользовательские модули
├── Themes/                 # Устанавливаемые темы
└── Helpers/                # Глобальные хелперы

config/                     # Конфигурация по умолчанию
config-dev/                 # Локальные переопределения (в .gitignore)
public/                     # Веб-корень — единственная открытая директория
storage/                    # Кэш, логи, загрузки (с правами на запись)
i18n/                       # Локализации
```

## Разработка модулей

Каждый модуль размещается в `app/Modules/<Name>/` с единой структурой:

```
MyModule/
├── module.json             # Метаданные, версия, зависимости
├── Providers/              # Сервис-провайдеры
├── Controllers/            # Контроллеры с атрибутами маршрутов
├── Services/               # Бизнес-логика
├── Resources/
│   ├── views/              # Blade-шаблоны
│   └── lang/               # Переводы
└── database/migrations/    # Автогенерируемые миграции
```

Маршруты задаются через PHP-атрибуты — без отдельных файлов роутов:

```php
#[Route('/my-module/example', name: 'my-module.example')]
public function example(): Response
{
    return view('MyModule::pages.example');
}
```

Полное руководство → [docs.flute-cms.com/en/modules](https://docs.flute-cms.com/en/modules)

## CLI

```bash
php flute cache:clear              # Очистить кэш приложения
php flute cache:warmup             # Прогреть кэш
php flute template:cache:clear     # Очистить скомпилированные шаблоны
php flute generate:migration       # Сгенерировать миграцию БД из сущностей
php flute generate:module          # Создать скелет нового модуля
php flute routes:list              # Вывести все зарегистрированные маршруты
php flute cron:run                 # Выполнить запланированные задачи
```

## Участие в разработке

Мы приветствуем любой вклад — баг-репорты, фичи, код, документацию и переводы.

1. Форкните репозиторий и создайте ветку от `early`
2. Внесите изменения и убедитесь, что `composer test` проходит
3. Отправьте pull request

**Ветки:**
- `main` — стабильные релизы
- `early` — активная разработка (направляйте PR сюда)

**Конвенция коммитов:** императив, со скоупом:
```
feat(auth): add OAuth2 authentication
fix(database): resolve connection timeout
core: fix router cache invalidation
module:Shop: add refund hook
```

Подробности в [CONTRIBUTING.md](CONTRIBUTING.md).

## Безопасность

Если вы обнаружили уязвимость, напишите на [flamesworkk@gmail.com](mailto:flamesworkk@gmail.com) вместо создания issue. Все вопросы безопасности будут оперативно рассмотрены.

## Сообщество

- [Discord](https://discord.gg/BcBMeVJJsd) — чат и поддержка
- [GitHub Issues](https://github.com/flute-cms/cms/issues) — баг-репорты и запросы фич
- [Документация](https://docs.flute-cms.com) — руководства и справка по API

## Лицензия

Flute — свободное ПО, распространяемое под лицензией [GNU General Public License v3.0 или более поздней версии](LICENSE).

---

<p align="center">Создано <a href="https://github.com/FlamesONE">Flames</a></p>
