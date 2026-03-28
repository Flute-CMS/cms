<?php

namespace Flute\Admin\Packages\MainSettings\Screens;

use Exception;
use Flute\Admin\Packages\MainSettings\Layouts\DatabaseSettingsLayout;
use Flute\Admin\Packages\MainSettings\Services\MainSettingsPackageService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\RadioCards;
use Flute\Admin\Platform\Fields\RichText;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Modules\Profile\Services\ProfileTabService;
use Flute\Core\Services\EmailService;
use Flute\Core\Support\FileUploader;
use Flute\Core\Support\FluteStr;
use PDO;
use RuntimeException;
use stdClass;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

use function constant;
use function defined;

class MainSettingsPackageScreen extends Screen
{
    public $databaseConnections;

    public $logo;

    public $logo_light;

    public $bg_image;

    public $bg_image_light;

    public $default_avatar;

    public $default_banner;

    public $favicon;

    public $social_image;

    public $profileTabs;

    protected string $name = 'admin-main-settings.labels.main_settings';

    protected ?string $description = 'admin-main-settings.labels.main_settings_description';

    protected $permission = 'admin.system';

    protected MainSettingsPackageService $configService;

    public function mount(): void
    {
        breadcrumb()->add(__('admin-main-settings.breadcrumbs.admin_panel'), url('/admin'))->add(__(
            'admin-main-settings.tabs.main_settings',
        ));

        $this->configService = app(MainSettingsPackageService::class);
        $this->databaseConnections = $this->configService->initDatabases();
        $this->loadProfileTabs();
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('admin-main-settings.buttons.clear_cache'))
                ->icon('ph.regular.cloud')
                ->method('clearCache')
                ->type(Color::OUTLINE_WARNING),

            Button::make(__('admin-main-settings.buttons.save'))->method('save'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::tabs([
                Tab::make(__('admin-main-settings.tabs.main_settings'))
                    ->slug('general')
                    ->icon('ph.bold.gear-bold')
                    ->layouts([
                        $this->mainSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.databases'))
                    ->slug('databases')
                    ->icon('ph.bold.cloud-bold')
                    ->layouts([
                        DatabaseSettingsLayout::class,
                    ]),
                Tab::make(__('admin-main-settings.tabs.users'))
                    ->slug('users')
                    ->icon('ph.bold.user-circle-bold')
                    ->layouts([
                        $this->usersSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.site'))
                    ->slug('site')
                    ->icon('ph.bold.browser-bold')
                    ->layouts([
                        $this->siteSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.mail'))
                    ->slug('mail')
                    ->icon('ph.bold.envelope-bold')
                    ->layouts([
                        $this->mailSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.localization'))
                    ->slug('localization')
                    ->icon('ph.bold.translate-bold')
                    ->layouts([
                        $this->localizationSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.advanced'))
                    ->slug('advanced')
                    ->icon('ph.bold.sliders-bold')
                    ->layouts([
                        $this->advancedSettingsLayout(),
                    ]),
            ])->slug('settings')->pills(),
        ];
    }

    public function testMail()
    {
        try {
            $to = user()->email;
            $mail = config('mail');

            $mail['smtp'] = (bool) ( request()->input('smtp') ?? $mail['smtp'] ?? false );
            $mail['from'] = request()->input('from') ?? $to;
            $mail['host'] = request()->input('host') ?? $mail['host'];
            $mail['port'] = request()->input('port') ?? $mail['port'];
            $mail['username'] = request()->input('username') ?? $mail['username'];
            $mail['password'] = request()->input('password') ?? $mail['password'];
            $mail['secure'] = request()->input('secure') ?? $mail['secure'];

            config()->set('mail', $mail);

            if (!$to) {
                $this->flashMessage(__('admin-main-settings.messages.sender_email_not_set'), 'error');

                return;
            }

            app(EmailService::class)->send($to, 'SMTP Test', 'This is a test email. bla bla bla');
            $this->flashMessage(__('admin-main-settings.messages.test_mail_sent'), 'success');
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function saveFluteImages()
    {
        if (!$this->validateImages()) {
            return;
        }

        /** @var FileUploader $uploader */
        $uploader = app(FileUploader::class);
        $uploadsDir = realpath(BASE_PATH . '/public/assets/uploads');

        if ($uploadsDir === false) {
            $this->addUploadDirectoryError();

            return;
        }

        $avatarError = $this->processImageUpload('logo', $uploader, $uploadsDir);
        $logoLightError = $this->processImageUpload('logo_light', $uploader, $uploadsDir);
        $bannerError = $this->processImageUpload('bg_image', $uploader, $uploadsDir);
        $bannerLightError = $this->processImageUpload('bg_image_light', $uploader, $uploadsDir);
        $faviconError = $this->processFixedFileReplace('favicon', BASE_PATH . '/public/favicon.ico');
        $socialImageError = $this->processFixedFileReplace(
            'social_image',
            BASE_PATH . '/public/assets/img/social-image.png',
        );

        if (
            $avatarError
            || $logoLightError
            || $bannerError
            || $bannerLightError
            || $faviconError
            || $socialImageError
        ) {
            $this->flashMessage(
                $avatarError ?? $logoLightError ?? $bannerError ?? $bannerLightError ?? $faviconError
                    ?? $socialImageError,
                'error',
            );

            return;
        }

        if (!isset($this->logo)) {
            $logoClear = request()->input('logo_clear');
            if ($logoClear === '1') {
                config()->set('app.logo', 'assets/img/logo.svg');
            }
        }

        if (!isset($this->logo_light)) {
            $logoLightClear = request()->input('logo_light_clear');
            if ($logoLightClear === '1') {
                config()->set('app.logo_light', 'assets/img/logo-light.svg');
            }
        }

        if (!isset($this->bg_image)) {
            $bgImageClear = request()->input('bg_image_clear');
            if ($bgImageClear === '1') {
                config()->set('app.bg_image', '');
            }
        }

        if (!isset($this->bg_image_light)) {
            $bgImageLightClear = request()->input('bg_image_light_clear');
            if ($bgImageLightClear === '1') {
                config()->set('app.bg_image_light', '');
            }
        }

        try {
            config()->save();
            $this->invalidateConfig('app');
            $this->invalidateSettingsCache();
            $this->flashMessage(__('admin-main-settings.messages.flute_images_saved'), 'success');
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    public function saveProfileImages()
    {
        if (!$this->validateProfileImages()) {
            return;
        }

        /** @var FileUploader $uploader */
        $uploader = app(FileUploader::class);
        $uploadsDir = realpath(BASE_PATH . '/public/assets/uploads');

        if ($uploadsDir === false) {
            $this->addProfileUploadDirectoryError();

            return;
        }

        $avatarError = $this->processProfileImageUpload(
            'default_avatar',
            $uploader,
            $uploadsDir,
            'profile.default_avatar',
            'assets/img/no_avatar.webp',
        );
        $bannerError = $this->processProfileImageUpload(
            'default_banner',
            $uploader,
            $uploadsDir,
            'profile.default_banner',
            'assets/img/no_banner.webp',
        );

        if ($avatarError || $bannerError) {
            $this->flashMessage($avatarError ?? $bannerError, 'error');

            return;
        }

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.profile_images_saved'), 'success');

            $this->clearCache();
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    public function save()
    {
        $currentTab = request()->input('tab-settings', FluteStr::slug(__('admin-main-settings.tabs.main_settings')));

        $debugBefore = (bool) config('app.debug');
        $devBefore = (bool) config('app.development_mode');
        $performanceBefore = (bool) config('app.is_performance');
        $localeBefore = (string) config('lang.locale');
        $availableBefore = (array) config('lang.available');

        try {
            $save = $this->configService->saveSettings($currentTab, request()->input());

            if ($save) {
                $this->flashMessage(__('admin-main-settings.messages.settings_saved_successfully'));

                $debugAfter = (bool) config('app.debug');
                $devAfter = (bool) config('app.development_mode');
                $performanceAfter = (bool) config('app.is_performance');

                $localeChanged = $localeBefore !== (string) config('lang.locale');
                $availableChanged = $availableBefore !== (array) config('lang.available');

                if (
                    $debugBefore !== $debugAfter
                    || $devBefore !== $devAfter
                    || $performanceBefore !== $performanceAfter
                    || $localeChanged
                    || $availableChanged
                ) {
                    $this->clearCache();
                } else {
                    // Always invalidate global layout and navbar caches
                    // to ensure settings changes are visible immediately
                    $this->invalidateSettingsCache();
                }
            }
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.settings_save_error') . $e->getMessage(), 'error');
        }
    }

    public function clearCache()
    {
        $cacheDir = storage_path('app/cache');
        $cacheStaleDir = storage_path('app/cache_stale');
        $cssCacheDir = public_path('assets/css/cache');
        $cssCacheStaleDir = public_path('assets/css/cache_stale');
        $jsCacheDir = public_path('assets/js/cache');
        $jsCacheStaleDir = public_path('assets/js/cache_stale');

        $full = (bool) request()->input('full', false);

        $cachePaths = [
            storage_path('app/views/*'),
            storage_path('app/translations/*'),
            storage_path('app/proxies/*'),
        ];

        if (!is_performance() || $full) {
            $cachePaths[] = storage_path('logs/*');
        }

        try {
            $filesystem = fs();

            // Discard any pending SWR tasks so they don't write stale data
            // back into the freshly cleared cache after response is sent.
            \Flute\Core\Cache\SWRQueue::flush();

            if (function_exists('cache_bump_epoch')) {
                cache_bump_epoch();
            }
            if (function_exists('cache_warmup_mark')) {
                cache_warmup_mark();
            }

            // Remove both cache and stale directories entirely.
            // Unlike SWR rotation, admin expects to see fresh data immediately,
            // so we wipe stale too — no stale fallback after explicit clear.
            if (is_dir($cacheStaleDir)) {
                $filesystem->remove($cacheStaleDir);
            }
            if (is_dir($cacheDir)) {
                $filesystem->remove($cacheDir);
            }
            @mkdir($cacheDir, 0o755, true);
            @mkdir($cacheStaleDir, 0o755, true);

            // Same for CSS/JS asset caches
            if (is_dir($cssCacheStaleDir)) {
                $filesystem->remove($cssCacheStaleDir);
            }
            if (is_dir($cssCacheDir)) {
                $filesystem->remove($cssCacheDir);
            }
            @mkdir($cssCacheDir, 0o755, true);

            if (is_dir($jsCacheStaleDir)) {
                $filesystem->remove($jsCacheStaleDir);
            }
            if (is_dir($jsCacheDir)) {
                $filesystem->remove($jsCacheDir);
            }
            @mkdir($jsCacheDir, 0o755, true);

            foreach ($cachePaths as $path) {
                $files = glob($path);
                if ($files) {
                    $filesystem->remove($files);
                }
            }

            $this->clearOpcache();

            $this->flashMessage(__('admin-main-settings.messages.cache_cleared_successfully'));
        } catch (IOException $e) {
            logs()->warning($e);
            $this->flashMessage(
                __('admin-main-settings.messages.cache_cleared_successfully') . ' (' . $e->getMessage() . ')',
                'warning',
            );
        }
    }

    public function addDatabaseModal(Repository $parameters)
    {
        $defaultConnection = config('database.connections.default');

        $explode = explode('\\', $defaultConnection->driver);

        $driver = str_replace('driver', '', strtolower(end($explode)));
        $supportsMysqlOptions = $driver === 'mysql';
        $supportsReconnect = in_array($driver, ['mysql', 'postgres'], true);

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                ButtonGroup::make('driver')
                    ->options([
                        'mysql' => [
                            'label' => 'MySQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                        'postgres' => [
                            'label' => 'PostgreSQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                    ])
                    ->value($driver)
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_driver'))
                ->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.database_name')),
            )
                ->label(__('admin-main-settings.labels.database_name'))
                ->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value($defaultConnection->connection->host)
                    ->placeholder(__('admin-main-settings.placeholders.db_host')),
            )
                ->label(__('admin-main-settings.labels.host'))
                ->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value($defaultConnection->connection->port)
                    ->placeholder(__('admin-main-settings.placeholders.db_port')),
            )
                ->label(__('admin-main-settings.labels.port'))
                ->required(),
            LayoutFactory::field(
                Input::make('user')->type('text')->placeholder(__('admin-main-settings.placeholders.db_user')),
            )
                ->label(__('admin-main-settings.labels.user'))
                ->required(),
            LayoutFactory::field(
                Input::make('database')->type('text')->placeholder(__('admin-main-settings.placeholders.db_database')),
            )
                ->label(__('admin-main-settings.labels.database'))
                ->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.db_password')),
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                ButtonGroup::make('persistent')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.persistent_connections'))
                ->popover(__('admin-main-settings.popovers.persistent_connections')),
            LayoutFactory::field(
                Input::make('init_sql')->type('text')->placeholder(__('admin-main-settings.placeholders.db_init_sql')),
            )
                ->label(__('admin-main-settings.labels.db_init_sql'))
                ->popover(__('admin-main-settings.popovers.db_init_sql'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('compression')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_compression'))
                ->popover(__('admin-main-settings.popovers.db_compression'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('reconnect')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('1')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_reconnect'))
                ->popover(__('admin-main-settings.popovers.db_reconnect'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('connect_timeout')
                    ->type('number')
                    ->value(5)
                    ->placeholder(__('admin-main-settings.placeholders.db_connect_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_connect_timeout'))
                ->popover(__('admin-main-settings.popovers.db_connect_timeout'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('read_timeout')
                    ->type('number')
                    ->value(30)
                    ->placeholder(__('admin-main-settings.placeholders.db_read_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_read_timeout'))
                ->popover(__('admin-main-settings.popovers.db_read_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('write_timeout')
                    ->type('number')
                    ->value(30)
                    ->placeholder(__('admin-main-settings.placeholders.db_write_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_write_timeout'))
                ->popover(__('admin-main-settings.popovers.db_write_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('prefix')->type('text')->placeholder(__('admin-main-settings.placeholders.db_prefix')),
            )
                ->label(__('admin-main-settings.labels.prefix'))
                ->popover(__('admin-main-settings.popovers.prefix'))
                ->small(__('admin-main-settings.examples.prefix')),
        ])
            ->method('addDatabase')
            ->title(__('admin-main-settings.modals.add_database'))
            ->applyButton(__('admin-main-settings.buttons.add'))
            ->right();
    }

    public function editDatabaseModal(Repository $parameters)
    {
        $databaseId = $parameters->get('databaseId');

        $dbConfig = config('database');
        $database = $dbConfig['databases'][$databaseId] ?? null;

        if (!$database) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        if ($databaseId === 'default') {
            $this->flashMessage(__('admin-main-settings.messages.cannot_edit_default_db'), 'error');

            return;
        }

        $connectionName = $database['connection'];
        $connectionConfig = $dbConfig['connections'][$connectionName] ?? null;

        if (!$connectionConfig) {
            $this->flashMessage(__('admin-main-settings.messages.connection_not_found'), 'error');

            return;
        }

        if ($connectionConfig instanceof \Cycle\Database\Config\MySQLDriverConfig) {
            $driver = 'mysql';
            $tcpConnection = $connectionConfig->connection;
        } elseif ($connectionConfig instanceof \Cycle\Database\Config\PostgresDriverConfig) {
            $driver = 'postgres';
            $tcpConnection = $connectionConfig->connection;
        } else {
            $driver = 'mysql';
            $tcpConnection = $connectionConfig->connection;
        }

        $supportsMysqlOptions = $driver === 'mysql';
        $supportsReconnect = in_array($driver, ['mysql', 'postgres'], true);

        $persistent = false;
        if (isset($tcpConnection->options) && is_array($tcpConnection->options)) {
            $persistent = (bool) ( $tcpConnection->options[PDO::ATTR_PERSISTENT] ?? false );
        }

        $initSql = null;
        $compression = false;
        if (isset($tcpConnection->options) && is_array($tcpConnection->options)) {
            $mysqlInitKey = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? constant('PDO::MYSQL_ATTR_INIT_COMMAND') : null;
            if ($mysqlInitKey !== null) {
                $initSql = $tcpConnection->options[$mysqlInitKey] ?? null;
            }
            $mysqlCompressKey = defined('PDO::MYSQL_ATTR_COMPRESS') ? constant('PDO::MYSQL_ATTR_COMPRESS') : null;
            if ($mysqlCompressKey !== null) {
                $compression = (bool) ( $tcpConnection->options[$mysqlCompressKey] ?? false );
            }
        }

        $reconnect = (bool) ( $connectionConfig->reconnect ?? true );

        $connectTimeout = null;
        $readTimeout = null;
        $writeTimeout = null;
        if (isset($tcpConnection->options) && is_array($tcpConnection->options)) {
            $mysqlConnectKey = defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                ? constant('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                : null;
            $mysqlReadKey = defined('PDO::MYSQL_ATTR_READ_TIMEOUT') ? constant('PDO::MYSQL_ATTR_READ_TIMEOUT') : null;
            $mysqlWriteKey = defined('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                ? constant('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                : null;

            $connectTimeout = $mysqlConnectKey !== null ? $tcpConnection->options[$mysqlConnectKey] ?? null : null;
            $connectTimeout ??= $tcpConnection->options[PDO::ATTR_TIMEOUT] ?? null;

            if ($mysqlReadKey !== null) {
                $readTimeout = $tcpConnection->options[$mysqlReadKey] ?? null;
            }
            if ($mysqlWriteKey !== null) {
                $writeTimeout = $tcpConnection->options[$mysqlWriteKey] ?? null;
            }
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                ButtonGroup::make('driver')
                    ->options([
                        'mysql' => [
                            'label' => 'MySQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                        'postgres' => [
                            'label' => 'PostgreSQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                    ])
                    ->value($driver)
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_driver'))
                ->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->value($databaseId)
                    ->placeholder(__('admin-main-settings.placeholders.database_name'))
                    ->readonly()
                    ->required(),
            )
                ->label(__('admin-main-settings.labels.database_name'))
                ->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value($tcpConnection->host)
                    ->placeholder(__('admin-main-settings.placeholders.db_host')),
            )
                ->label(__('admin-main-settings.labels.host'))
                ->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value($tcpConnection->port)
                    ->placeholder(__('admin-main-settings.placeholders.db_port')),
            )
                ->label(__('admin-main-settings.labels.port'))
                ->required(),
            LayoutFactory::field(
                Input::make('user')
                    ->type('text')
                    ->value($tcpConnection->user)
                    ->placeholder(__('admin-main-settings.placeholders.db_user')),
            )
                ->label(__('admin-main-settings.labels.user'))
                ->required(),
            LayoutFactory::field(
                Input::make('database')
                    ->type('text')
                    ->value($tcpConnection->database)
                    ->placeholder(__('admin-main-settings.placeholders.db_database')),
            )
                ->label(__('admin-main-settings.labels.database'))
                ->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->value($tcpConnection->password)
                    ->placeholder(__('admin-main-settings.placeholders.db_password')),
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                ButtonGroup::make('persistent')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($persistent ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.persistent_connections'))
                ->popover(__('admin-main-settings.popovers.persistent_connections')),
            LayoutFactory::field(
                Input::make('init_sql')
                    ->type('text')
                    ->value($initSql)
                    ->placeholder(__('admin-main-settings.placeholders.db_init_sql')),
            )
                ->label(__('admin-main-settings.labels.db_init_sql'))
                ->popover(__('admin-main-settings.popovers.db_init_sql'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('compression')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($compression ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_compression'))
                ->popover(__('admin-main-settings.popovers.db_compression'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('reconnect')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($reconnect ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_reconnect'))
                ->popover(__('admin-main-settings.popovers.db_reconnect'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('connect_timeout')
                    ->type('number')
                    ->value($connectTimeout ?? 5)
                    ->placeholder(__('admin-main-settings.placeholders.db_connect_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_connect_timeout'))
                ->popover(__('admin-main-settings.popovers.db_connect_timeout'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('read_timeout')
                    ->type('number')
                    ->value($readTimeout ?? 30)
                    ->placeholder(__('admin-main-settings.placeholders.db_read_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_read_timeout'))
                ->popover(__('admin-main-settings.popovers.db_read_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('write_timeout')
                    ->type('number')
                    ->value($writeTimeout ?? 30)
                    ->placeholder(__('admin-main-settings.placeholders.db_write_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_write_timeout'))
                ->popover(__('admin-main-settings.popovers.db_write_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('prefix')
                    ->type('text')
                    ->value($database['prefix'])
                    ->placeholder(__('admin-main-settings.placeholders.db_prefix')),
            )
                ->label(__('admin-main-settings.labels.prefix'))
                ->popover(__('admin-main-settings.popovers.prefix'))
                ->small(__('admin-main-settings.examples.prefix')),
        ])
            ->method('changeDatabase')
            ->title(__('admin-main-settings.modals.edit_database', ['db' => $databaseId]))
            ->applyButton(__('admin-main-settings.buttons.save'))
            ->right();
    }

    public function addDatabase()
    {
        $data = request()->input();

        if (!$this->validate([
            'driver' => ['required', 'string', 'in:mysql,postgres'],
            'databaseName' => ['required', 'string', 'not-in:default'],
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'user' => ['required', 'string'],
            'database' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'persistent' => ['nullable'],
            'init_sql' => ['nullable', 'string'],
            'compression' => ['nullable'],
            'reconnect' => ['nullable'],
            'connect_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'read_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'write_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'prefix' => ['nullable', 'string'],
        ], request()->input())) {
            return;
        }

        $connectionTest = $this->configService->testDatabaseConnection(
            $data['driver'],
            $data['host'],
            (int) $data['port'],
            $data['database'],
            $data['user'],
            $data['password'] ?? null,
        );

        if ($connectionTest !== true) {
            $this->flashMessage(
                __('admin-main-settings.messages.connection_test_failed') . ': ' . $connectionTest,
                'error',
            );

            return;
        }

        $databaseName = $data['databaseName'];
        $driver = $data['driver'];
        $persistent = filter_var($data['persistent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $initSql = trim((string) ( $data['init_sql'] ?? '' ));
        $compression = filter_var($data['compression'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $reconnect = filter_var($data['reconnect'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $connectTimeout = isset($data['connect_timeout']) ? (int) $data['connect_timeout'] : null;
        $readTimeout = isset($data['read_timeout']) ? (int) $data['read_timeout'] : null;
        $writeTimeout = isset($data['write_timeout']) ? (int) $data['write_timeout'] : null;

        $databases = config()->get('database.databases', []);
        if (isset($databases[$databaseName])) {
            $this->flashMessage(__('admin-main-settings.messages.database_exists'), 'error');

            return;
        }

        config()->set("database.databases.{$databaseName}", [
            'connection' => $databaseName,
            'prefix' => $data['prefix'] ?? '',
        ]);

        if ($driver === 'mysql') {
            $options = [];
            $mysqlInitKey = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? constant('PDO::MYSQL_ATTR_INIT_COMMAND') : null;
            if ($mysqlInitKey !== null) {
                $options[$mysqlInitKey] = $initSql !== '' ? $initSql : 'SET NAMES utf8mb4';
            }
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            if ($compression) {
                $mysqlCompressKey = defined('PDO::MYSQL_ATTR_COMPRESS') ? constant('PDO::MYSQL_ATTR_COMPRESS') : null;
                if ($mysqlCompressKey !== null) {
                    $options[$mysqlCompressKey] = true;
                }
            }
            if ($connectTimeout !== null) {
                $mysqlConnectKey = defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                    : null;
                if ($mysqlConnectKey !== null) {
                    $options[$mysqlConnectKey] = $connectTimeout;
                }
                $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
            }
            if ($readTimeout !== null) {
                $mysqlReadKey = defined('PDO::MYSQL_ATTR_READ_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_READ_TIMEOUT')
                    : null;
                if ($mysqlReadKey !== null) {
                    $options[$mysqlReadKey] = $readTimeout;
                }
            }
            if ($writeTimeout !== null) {
                $mysqlWriteKey = defined('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                    : null;
                if ($mysqlWriteKey !== null) {
                    $options[$mysqlWriteKey] = $writeTimeout;
                }
            }
            $connectionConfig = new \Cycle\Database\Config\MySQLDriverConfig(
                connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: $options,
                ),
                reconnect: $reconnect,
                timezone: 'Asia/Yekaterinburg',
                queryCache: true,
                readonlySchema: true,
            );
        } elseif ($driver === 'postgres') {
            $options = [];
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            if ($connectTimeout !== null) {
                $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
            }
            $connectionConfig = new \Cycle\Database\Config\PostgresDriverConfig(
                connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: $options,
                ),
                reconnect: $reconnect,
                schema: 'public',
                queryCache: true,
                readonlySchema: true,
            );
        } else {
            $this->flashMessage(__('admin-main-settings.messages.unsupported_driver'), 'error');

            return;
        }

        config()->set("database.connections.{$databaseName}", $connectionConfig);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.add_database_success'));
            $this->databaseConnections = $this->configService->initDatabases();

            $this->closeModal();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.add_database_error') . $e->getMessage(), 'error');
        }
    }

    public function changeDatabase()
    {
        $data = request()->input();

        if (!$this->validate([
            'driver' => ['required', 'string', 'in:mysql,postgres'],
            'databaseName' => ['required', 'string', 'not-in:default'],
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'user' => ['required', 'string'],
            'database' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'persistent' => ['nullable'],
            'init_sql' => ['nullable', 'string'],
            'compression' => ['nullable'],
            'reconnect' => ['nullable'],
            'connect_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'read_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'write_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'prefix' => ['nullable', 'string'],
        ], request()->input())) {
            return;
        }

        $connectionTest = $this->configService->testDatabaseConnection(
            $data['driver'],
            $data['host'],
            (int) $data['port'],
            $data['database'],
            $data['user'],
            $data['password'] ?? null,
        );

        if ($connectionTest !== true) {
            $this->flashMessage(
                __('admin-main-settings.messages.connection_test_failed') . ': ' . $connectionTest,
                'error',
            );

            return;
        }

        $databaseName = $data['databaseName'];
        $driver = $data['driver'];
        $persistent = filter_var($data['persistent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $initSql = trim((string) ( $data['init_sql'] ?? '' ));
        $compression = filter_var($data['compression'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $reconnect = filter_var($data['reconnect'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $connectTimeout = isset($data['connect_timeout']) ? (int) $data['connect_timeout'] : null;
        $readTimeout = isset($data['read_timeout']) ? (int) $data['read_timeout'] : null;
        $writeTimeout = isset($data['write_timeout']) ? (int) $data['write_timeout'] : null;

        $databases = config('database.databases');
        if (!isset($databases[$databaseName])) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        config()->set("database.databases.{$databaseName}.prefix", $data['prefix'] ?? '');

        if ($driver === 'mysql') {
            $options = [];
            $mysqlInitKey = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? constant('PDO::MYSQL_ATTR_INIT_COMMAND') : null;
            if ($mysqlInitKey !== null) {
                $options[$mysqlInitKey] = $initSql !== '' ? $initSql : 'SET NAMES utf8mb4';
            }
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            if ($compression) {
                $mysqlCompressKey = defined('PDO::MYSQL_ATTR_COMPRESS') ? constant('PDO::MYSQL_ATTR_COMPRESS') : null;
                if ($mysqlCompressKey !== null) {
                    $options[$mysqlCompressKey] = true;
                }
            }
            if ($connectTimeout !== null) {
                $mysqlConnectKey = defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                    : null;
                if ($mysqlConnectKey !== null) {
                    $options[$mysqlConnectKey] = $connectTimeout;
                }
                $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
            }
            if ($readTimeout !== null) {
                $mysqlReadKey = defined('PDO::MYSQL_ATTR_READ_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_READ_TIMEOUT')
                    : null;
                if ($mysqlReadKey !== null) {
                    $options[$mysqlReadKey] = $readTimeout;
                }
            }
            if ($writeTimeout !== null) {
                $mysqlWriteKey = defined('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                    : null;
                if ($mysqlWriteKey !== null) {
                    $options[$mysqlWriteKey] = $writeTimeout;
                }
            }
            $connectionConfig = new \Cycle\Database\Config\MySQLDriverConfig(
                connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: $options,
                ),
                reconnect: $reconnect,
                timezone: 'Asia/Yekaterinburg',
                queryCache: true,
                readonlySchema: true,
            );
        } elseif ($driver === 'postgres') {
            $options = [];
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            if ($connectTimeout !== null) {
                $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
            }
            $connectionConfig = new \Cycle\Database\Config\PostgresDriverConfig(
                connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: $options,
                ),
                reconnect: $reconnect,
                schema: 'public',
                queryCache: true,
                readonlySchema: true,
            );
        } else {
            $this->flashMessage(__('admin-main-settings.messages.unsupported_driver'), 'error');

            return;
        }

        config()->set("database.connections.{$databaseName}", $connectionConfig);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.edit_database_success'));
            $this->closeModal();
            $this->databaseConnections = $this->configService->initDatabases();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.edit_database_error') . $e->getMessage(), 'error');
        }
    }

    public function removeDatabase()
    {
        $data = request()->input();

        if (!$this->validate([
            'databaseId' => ['required', 'string', 'not-in:default'],
        ], request()->input())) {
            return;
        }

        $databaseId = $data['databaseId'];

        $databases = config()->get('database.databases', []);
        $connections = config()->get('database.connections', []);

        if (!isset($databases[$databaseId])) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        unset($databases[$databaseId]);
        unset($connections[$databaseId]);

        config()->set('database.databases', $databases);
        config()->set('database.connections', $connections);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.remove_database_success'));
            $this->databaseConnections = $this->configService->initDatabases();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.remove_database_error'), 'error');
        }
    }

    public function saveProfileTabsOrder()
    {
        $sortableResult = json_decode(request()->input('sortableResult', '[]'), true);

        if (!$sortableResult) {
            $this->flashMessage(__('admin-main-settings.messages.invalid_sort'), 'error');

            return;
        }

        $order = [];
        foreach ($sortableResult as $item) {
            if (isset($item['id'])) {
                $order[] = $item['id'];
            }
        }

        config()->set('profile.tabs_order', $order);

        try {
            config()->save();
            cache()->deleteImmediately('profile_tabs_cache');
            $this->flashMessage(__('admin-main-settings.messages.profile_tabs_order_saved'));
            $this->loadProfileTabs();
            $this->invalidateConfig('profile');
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    protected function loadProfileTabs(): void
    {
        if (app()->has(ProfileTabService::class)) {
            $profileTabService = app(ProfileTabService::class);
            $this->profileTabs = $profileTabService->getUniqueTabPaths()->map(static function ($tab) {
                $obj = new stdClass();
                $obj->id = $tab['id'];
                $obj->path = $tab['path'];
                $obj->title = $tab['title'];
                $obj->icon = $tab['icon'];

                return $obj;
            });
        } else {
            $this->profileTabs = collect();
        }
    }

    protected function validateImages(): bool
    {
        $this->logo = request()->files->get('logo');
        $this->logo_light = request()->files->get('logo_light');
        $this->bg_image = request()->files->get('bg_image');
        $this->bg_image_light = request()->files->get('bg_image_light');
        $this->favicon = request()->files->get('favicon');
        $this->social_image = request()->files->get('social_image');

        $rules = [
            'logo' => $this->logo ? 'image|max-file-size:10240' : 'nullable|image|max-file-size:10240',
            'logo_light' => $this->logo_light ? 'image|max-file-size:10240' : 'nullable|image|max-file-size:10240',
            'bg_image' => $this->bg_image ? 'image|max-file-size:10240' : 'nullable|image|max-file-size:10240',
            'bg_image_light' => $this->bg_image_light
                ? 'image|max-file-size:10240'
                : 'nullable|image|max-file-size:10240',
            'favicon' => $this->favicon ? 'mimes:ico|max-file-size:2048' : 'nullable|mimes:ico|max-file-size:2048',
            'social_image' => $this->social_image
                ? 'image|mimes:png|max-file-size:10240'
                : 'nullable|image|mimes:png|max-file-size:10240',
        ];

        return $this->validate($rules);
    }

    protected function invalidateConfig(string $configName): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(path('config/' . $configName . '.php'), true);
        }
    }

    /**
     * Invalidate caches that depend on main settings (layout, navbar, footer, etc.).
     */
    protected function invalidateSettingsCache(): void
    {
        try {
            cache()->deleteImmediately('flute.global.layout');
            cache()->deleteByTag(\Flute\Core\Services\NavbarService::CACHE_TAG);
            cache()->deleteByTag(\Flute\Core\Services\FooterService::CACHE_TAG);
        } catch (Throwable $e) {
            // Do not break admin flow if cache clearing fails
        }
    }

    protected function processImageUpload(string $field, FileUploader $uploader, string $uploadsDir): ?string
    {
        $file = $this->$field;
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $newFile = $uploader->uploadImage($file, 10);

                if ($newFile === null) {
                    throw new RuntimeException(__('admin-main-settings.messages.upload_failed', ['field' => $field]));
                }

                config()->set("app.{$field}", $newFile);

                return null;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    /**
     * Replace a target file in public path with the uploaded file, using a fixed filename.
     * Also handles file deletion when the clear flag is set.
     */
    protected function processFixedFileReplace(string $field, string $absoluteTargetPath): ?string
    {
        $file = $this->$field;
        $clearFlag = request()->input($field . '_clear');

        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $dir = dirname($absoluteTargetPath);
                $filesystem = fs();
                if (!is_dir($dir)) {
                    $filesystem->mkdir($dir, 0o755);
                }

                if (file_exists($absoluteTargetPath)) {
                    $filesystem->remove($absoluteTargetPath);
                }

                $file->move($dir, basename($absoluteTargetPath));

                return null;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        if ($clearFlag === '1' && file_exists($absoluteTargetPath)) {
            try {
                fs()->remove($absoluteTargetPath);
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    /**
     * Add errors when the upload directory does not exist.
     */
    protected function addUploadDirectoryError(): void
    {
        $this->inputError('logo', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('logo_light', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('bg_image', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('bg_image_light', __('admin-main-settings.messages.upload_directory_error'));
    }

    /**
     * Validate profile images.
     */
    protected function validateProfileImages(): bool
    {
        $this->default_avatar = request()->files->get('default_avatar');
        $this->default_banner = request()->files->get('default_banner');

        $rules = [
            'default_avatar' => $this->default_avatar
                ? 'image|mimes:jpeg,png,jpg,gif,svg,webp|max-file-size:10240'
                : 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max-file-size:10240',
            'default_banner' => $this->default_banner
                ? 'image|mimes:jpeg,png,jpg,gif,webp|max-file-size:10240'
                : 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max-file-size:10240',
        ];

        return $this->validate($rules);
    }

    /**
     * Process profile image upload.
     *
     * @param string $field Input field (default_avatar or default_banner)
     * @param FileUploader $uploader File uploader instance
     * @param string $uploadsDir Upload directory path
     * @param string $configKey Configuration key for saving path
     * @param string $defaultFile Default file path
     * @return string|null Error message or null
     */
    protected function processProfileImageUpload(
        string $field,
        FileUploader $uploader,
        string $uploadsDir,
        string $configKey,
        string $defaultFile,
    ): ?string {
        $file = $this->$field;
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $newFile = $uploader->uploadImage($file, 10);

                if ($newFile === null) {
                    throw new RuntimeException(__('admin-main-settings.messages.upload_failed', ['field' => $field]));
                }

                config()->set($configKey, $newFile);

                return null;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        if ($field === 'default_avatar') {
            config()->set($configKey, 'assets/img/no_avatar.webp');
        } elseif ($field === 'default_banner') {
            config()->set($configKey, 'assets/img/no_banner.webp');
        }

        return null;
    }

    protected function addProfileUploadDirectoryError(): void
    {
        $this->inputError('default_avatar', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('default_banner', __('admin-main-settings.messages.upload_directory_error'));
    }

    private function mainSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.main_settings'))
                ->slug('main')
                ->layouts([
                    LayoutFactory::split([
                        $this->mainSettingsMainBlock(),
                        $this->mainSettingsMaintenanceBlock(),
                    ])->ratio('60/40'),
                ]),
            Tab::make(__('admin-main-settings.blocks.seo'))
                ->slug('seo')
                ->layouts([
                    $this->mainSettingsSeoBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.branding'))
                ->slug('branding')
                ->icon('ph.bold.paint-brush-bold')
                ->layouts([
                    $this->additionalSettingsImagesBlock(),
                ]),
        ])
            ->slug('main_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function mainSettingsMainBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('name')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.site_name'))
                        ->value(config('app.name')),
                )
                    ->label(__('admin-main-settings.labels.site_name'))
                    ->required(),
                LayoutFactory::field(
                    Input::make('url')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.site_url'))
                        ->value(config('app.url')),
                )
                    ->label(__('admin-main-settings.labels.site_url'))
                    ->required(),
            ]),
            LayoutFactory::field(
                Input::make('timezone')
                    ->placeholder(__('admin-main-settings.placeholders.timezone'))
                    ->value(config('app.timezone')),
            )
                ->label(__('admin-main-settings.labels.timezone'))
                ->required()
                ->small(__('admin-main-settings.examples.timezone')),
            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('change_theme')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.change_theme') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.change_theme'))
                    ->popover(__('admin-main-settings.popovers.change_theme')),
                LayoutFactory::field(
                    RadioCards::make('default_theme')
                        ->options([
                            'dark' => [
                                'label' => __('admin-main-settings.options.theme.dark'),
                                'icon' => 'ph.bold.moon-bold',
                            ],
                            'light' => [
                                'label' => __('admin-main-settings.options.theme.light'),
                                'icon' => 'ph.bold.sun-bold',
                            ],
                        ])
                        ->columns(2)
                        ->value(config('app.default_theme', 'dark')),
                )
                    ->label(__('admin-main-settings.labels.default_theme'))
                    ->popover(__('admin-main-settings.popovers.default_theme')),
            ])->ratio('50/50'),
            LayoutFactory::field(
                Input::make('flute_key')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.flute_key'))
                    ->value(config('app.flute_key')),
            )
                ->label(__('admin-main-settings.labels.flute_key'))
                ->popover(__('admin-main-settings.popovers.flute_key')),
            LayoutFactory::field(
                Input::make('steam_api')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.steam_api'))
                    ->value(config('app.steam_api')),
            )
                ->label(__('admin-main-settings.labels.steam_api'))
                ->popover(__('admin-main-settings.popovers.steam_api')),
            LayoutFactory::field(
                Input::make('steam_cache_duration')
                    ->type('number')
                    ->placeholder(__('admin-main-settings.placeholders.steam_cache_duration'))
                    ->value(config('app.steam_cache_duration', 3600)),
            )
                ->label(__('admin-main-settings.labels.steam_cache_duration'))
                ->popover(__('admin-main-settings.popovers.steam_cache_duration'))
                ->small(__('admin-main-settings.examples.steam_cache_duration')),
            LayoutFactory::field(
                TextArea::make('footer_description')
                    ->placeholder(__('admin-main-settings.placeholders.footer_description'))
                    ->value(config('app.footer_description')),
            )->label(__('admin-main-settings.labels.footer_description')),
            LayoutFactory::field(
                RichText::make('footer_additional')
                    ->toolbarPreset('minimal')
                    ->height(100)
                    ->placeholder(__('admin-main-settings.placeholders.footer_additional'))
                    ->value(config('app.footer_additional')),
            )->label(__('admin-main-settings.labels.footer_additional')),
        ])->title(__('admin-main-settings.blocks.main_settings'))->addClass('mb-2');
    }

    private function mainSettingsMaintenanceBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                ButtonGroup::make('maintenance_mode')
                    ->options([
                        '0' => [
                            'label' => __('admin-main-settings.options.site_status.open'),
                            'icon' => 'ph.bold.globe-bold',
                        ],
                        '1' => [
                            'label' => __('admin-main-settings.options.site_status.closed'),
                            'icon' => 'ph.bold.lock-bold',
                        ],
                    ])
                    ->value(config('app.maintenance_mode') ? '1' : '0')
                    ->color('accent')
                    ->fullWidth(),
            )
                ->label(__('admin-main-settings.labels.site_status'))
                ->popover(__('admin-main-settings.popovers.maintenance_mode')),
            LayoutFactory::field(
                TextArea::make('maintenance_message')
                    ->placeholder(__('admin-main-settings.placeholders.maintenance_message'))
                    ->value(config('app.maintenance_message')),
            )->label(__('admin-main-settings.labels.maintenance_message')),
        ])->title(__('admin-main-settings.blocks.tech_work_settings'))->addClass('mb-2');
    }

    private function mainSettingsSeoBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('keywords')
                    ->placeholder(__('admin-main-settings.placeholders.keywords'))
                    ->value(config('app.keywords')),
            )
                ->label(__('admin-main-settings.labels.keywords'))
                ->required()
                ->small(__('admin-main-settings.examples.keywords')),

            LayoutFactory::field(Select::make('robots')
                ->value(config('app.robots', 'index, follow'))
                ->aligned()
                ->options([
                    'index, follow' => __('admin-main-settings.options.robots.index_follow'),
                    'index, nofollow' => __('admin-main-settings.options.robots.index_nofollow'),
                    'noindex, nofollow' => __('admin-main-settings.options.robots.noindex_nofollow'),
                    'noindex, follow' => __('admin-main-settings.options.robots.noindex_follow'),
                ]))
                ->label(__('admin-main-settings.labels.robots'))
                ->required()
                ->small(__('admin-main-settings.examples.robots')),

            LayoutFactory::field(
                Input::make('description')
                    ->placeholder(__('admin-main-settings.placeholders.description'))
                    ->value(config('app.description')),
            )->label(__('admin-main-settings.labels.description')),
        ])
            ->title(__('admin-main-settings.blocks.seo'))
            ->addClass('mb-2')
            ->popover(__('admin-main-settings.popovers.seo'));
    }

    private function advancedPerformanceBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('is_performance')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.lightning-bold'],
                        ])
                        ->value(config('app.is_performance') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.is_performance'))
                    ->popover(__('admin-main-settings.popovers.is_performance')),
                LayoutFactory::field(
                    ButtonGroup::make('cron_mode')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.clock-bold'],
                        ])
                        ->value(config('app.cron_mode') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.cron_mode'))
                    ->popover(__('admin-main-settings.popovers.cron_mode')),
            ]),
            LayoutFactory::view('admin-main-settings::cron')->setVisible(boolval(config('app.cron_mode'))),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('convert_to_webp')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.image-bold'],
                        ])
                        ->value(config('app.convert_to_webp') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.convert_to_webp'))
                    ->popover(__('admin-main-settings.popovers.convert_to_webp')),
                LayoutFactory::field(
                    ButtonGroup::make('minify')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.file-zip-bold'],
                        ])
                        ->value(config('assets.minify') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.minify'))
                    ->small(__('admin-main-settings.labels.minify_description')),
                LayoutFactory::field(
                    ButtonGroup::make('autoprefix')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.browsers-bold'],
                        ])
                        ->value(config('assets.autoprefix', false) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.autoprefix'))
                    ->small(__('admin-main-settings.labels.autoprefix_description')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('create_backup')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.download-bold'],
                        ])
                        ->value(config('app.create_backup', false) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.create_backup'))
                    ->popover(__('admin-main-settings.popovers.create_backup')),
                LayoutFactory::field(
                    ButtonGroup::make('auto_update')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.arrow-clockwise-bold'],
                        ])
                        ->value(config('app.auto_update', false) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.auto_update'))
                    ->setVisible(config('app.cron_mode'))
                    ->popover(__('admin-main-settings.popovers.auto_update')),
                LayoutFactory::field(
                    ButtonGroup::make('csrf_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.shield-check-bold'],
                        ])
                        ->value(config('app.csrf_enabled') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.csrf_enabled'))
                    ->popover(__('admin-main-settings.popovers.csrf_enabled')),
            ]),
        ])
            ->title(__('admin-main-settings.blocks.performance'))
            ->addClass('mb-2')
            ->description(__('admin-main-settings.blocks.performance_description'));
    }

    private function advancedDebugBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('debug')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.debug.off'),
                                'icon' => 'ph.bold.eye-slash-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.debug.on'),
                                'icon' => 'ph.bold.bug-bold',
                            ],
                        ])
                        ->value(is_development() || config('app.debug') ? '1' : '0')
                        ->disabled(is_development())
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.debug'))
                    ->popover(__('admin-main-settings.popovers.debug')),
                LayoutFactory::field(
                    ButtonGroup::make('development_mode')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.mode.production'),
                                'icon' => 'ph.bold.rocket-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.mode.development'),
                                'icon' => 'ph.bold.wrench-bold',
                            ],
                        ])
                        ->value(config('app.development_mode') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.site_mode'))
                    ->popover(__('admin-main-settings.popovers.development_mode')),
            ]),
            LayoutFactory::field(
                Input::make('debug_ips')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.debug_ips'))
                    ->value(is_array(config('app.debug_ips')) ? implode(', ', config('app.debug_ips')) : ''),
            )
                ->label(__('admin-main-settings.labels.debug_ips'))
                ->popover(__('admin-main-settings.popovers.debug_ips'))
                ->small(__('admin-main-settings.examples.debug_ips')),
        ])->title(__('admin-main-settings.blocks.debug_settings'))->addClass('mb-2');
    }

    private function mainSettingsPersonalCabinetBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('currency_view')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.currency_view'))
                    ->value(config('lk.currency_view')),
            )
                ->label(__('admin-main-settings.labels.currency_view'))
                ->popover(__('admin-main-settings.popovers.currency_view')),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('oferta_view')
                        ->options([
                            '0' => ['label' => __('def.hide'), 'icon' => 'ph.bold.eye-slash-bold'],
                            '1' => ['label' => __('def.show'), 'icon' => 'ph.bold.eye-bold'],
                        ])
                        ->value(config('lk.oferta_view') ? '1' : '0')
                        ->color('accent'),
                )->label(__('admin-main-settings.labels.oferta_view')),
                LayoutFactory::field(
                    ButtonGroup::make('lk_only_modal')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.lk.page'),
                                'icon' => 'ph.bold.browser-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.lk.modal'),
                                'icon' => 'ph.bold.frame-corners-bold',
                            ],
                        ])
                        ->value(config('lk.only_modal') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.lk_only_modal'))
                    ->popover(__('admin-main-settings.popovers.lk_only_modal')),
                LayoutFactory::field(
                    ButtonGroup::make('lk_step_mode')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.steps-bold'],
                        ])
                        ->value(config('lk.step_mode') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.lk_step_mode'))
                    ->popover(__('admin-main-settings.popovers.lk_step_mode')),
            ]),
            LayoutFactory::field(
                Input::make('oferta_url')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.oferta_url'))
                    ->value(config('lk.oferta_url')),
            )
                ->label(__('admin-main-settings.labels.oferta_url'))
                ->popover(__('admin-main-settings.popovers.oferta_url'))
                ->small(__('admin-main-settings.examples.oferta_url')),
        ])->title(__('admin-main-settings.blocks.personal_cabinet_settings'))->addClass('mb-2');
    }

    private function mainSettingsSiteModeBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('auth_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.auth_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.auth_enabled'))
                    ->popover(__('admin-main-settings.popovers.auth_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('profile_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.profile_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.profile_enabled'))
                    ->popover(__('admin-main-settings.popovers.profile_enabled')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('balance_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.balance_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.balance_enabled'))
                    ->popover(__('admin-main-settings.popovers.balance_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('notifications_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.notifications_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.notifications_enabled'))
                    ->popover(__('admin-main-settings.popovers.notifications_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('notifications_popup_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.notifications_popup_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.notifications_popup_enabled'))
                    ->popover(__('admin-main-settings.popovers.notifications_popup_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('notifications_sound_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.notifications_sound_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.notifications_sound_enabled'))
                    ->popover(__('admin-main-settings.popovers.notifications_sound_enabled')),
            ]),
        ])
            ->title(__('admin-main-settings.blocks.features'))
            ->description(__('admin-main-settings.blocks.features_description'))
            ->addClass('mb-2');
    }

    private function usersSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.auth_settings'))->layouts([
                $this->usersAuthBlock(),
                $this->usersSessionBlock(),
            ]),
            Tab::make(__('admin-main-settings.blocks.captcha_settings'))->layouts([
                $this->usersCaptchaBlock(),
            ]),
            Tab::make(__('admin-main-settings.blocks.two_factor_settings'))->layouts([
                $this->usersTwoFactorBlock(),
            ]),
            Tab::make(__('admin-main-settings.blocks.profile_settings'))->layouts([
                $this->usersProfileBlock(),
                $this->usersProfileTabsOrderBlock(),
            ]),
        ])
            ->slug('users_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function siteSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.features'))
                ->icon('ph.bold.toggle-left-bold')
                ->layouts([
                    $this->mainSettingsSiteModeBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.personal_cabinet_settings'))
                ->icon('ph.bold.wallet-bold')
                ->layouts([
                    $this->mainSettingsPersonalCabinetBlock(),
                ]),
        ])
            ->slug('site_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function usersAuthBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('reset_password')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.reset_password') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.reset_password'))
                    ->popover(__('admin-main-settings.popovers.reset_password')),
                LayoutFactory::field(
                    ButtonGroup::make('only_social')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.only_social') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.only_social'))
                    ->popover(__('admin-main-settings.popovers.only_social')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('only_modal')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.auth.page'),
                                'icon' => 'ph.bold.browser-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.auth.modal'),
                                'icon' => 'ph.bold.frame-corners-bold',
                            ],
                        ])
                        ->value(config('auth.only_modal') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.only_modal'))
                    ->popover(__('admin-main-settings.popovers.only_modal')),
                LayoutFactory::field(
                    ButtonGroup::make('confirm_email')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.envelope-bold'],
                        ])
                        ->value(config('auth.registration.confirm_email') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.confirm_email'))
                    ->popover(__('admin-main-settings.popovers.confirm_email')),
                LayoutFactory::field(
                    ButtonGroup::make('social_supplement')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.user-plus-bold'],
                        ])
                        ->value(config('auth.registration.social_supplement') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.social_supplement'))
                    ->popover(__('admin-main-settings.popovers.social_supplement')),
            ]),
            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('remember_me')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.remember_me') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.remember_me'))
                    ->popover(__('admin-main-settings.popovers.remember_me')),
                LayoutFactory::field(
                    Input::make('remember_me_duration')
                        ->type('number')
                        ->placeholder(__('admin-main-settings.placeholders.remember_me_duration'))
                        ->value(config('auth.remember_me_duration')),
                )
                    ->label(__('admin-main-settings.labels.remember_me_duration'))
                    ->small(__('admin-main-settings.examples.remember_me_duration')),
            ]),
            LayoutFactory::field(
                Select::make('default_role')
                    ->fromDatabase('roles', 'name', 'id', ['name', 'id'])
                    ->placeholder(__('admin-main-settings.placeholders.default_role_placeholder'))
                    ->value(config('auth.default_role', 0)),
            )
                ->label(__('admin-main-settings.labels.default_role'))
                ->popover(__('admin-main-settings.popovers.default_role')),
        ])->title(__('admin-main-settings.blocks.auth_settings'))->addClass('mb-3');
    }

    private function usersSessionBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('check_ip')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.map-pin-bold'],
                        ])
                        ->value(config('auth.check_ip') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.check_ip'))
                    ->popover(__('admin-main-settings.popovers.check_ip')),
                LayoutFactory::field(
                    ButtonGroup::make('security_token')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.key-bold'],
                        ])
                        ->value(config('auth.security_token') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.security_token'))
                    ->popover(__('admin-main-settings.popovers.security_token')),
            ]),
        ])->title(__('admin-main-settings.blocks.session_settings'))->description(__(
            'admin-main-settings.blocks.session_description',
        ));
    }

    private function usersCaptchaBlock()
    {
        $captchaType = request()->input('captcha_type', config('auth.captcha.type'));

        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('captcha_enabled_login')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.captcha.enabled.login') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.captcha_enabled_login'))
                    ->popover(__('admin-main-settings.popovers.captcha_enabled_login')),
                LayoutFactory::field(
                    ButtonGroup::make('captcha_enabled_register')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.captcha.enabled.register') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.captcha_enabled_register'))
                    ->popover(__('admin-main-settings.popovers.captcha_enabled_register')),
            ]),
            LayoutFactory::field(
                ButtonGroup::make('captcha_enabled_password_reset')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value(config('auth.captcha.enabled.password_reset') ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.captcha_enabled_password_reset'))
                ->popover(__('admin-main-settings.popovers.captcha_enabled_password_reset')),
            LayoutFactory::field(
                ButtonGroup::make('captcha_type')
                    ->yoyo()
                    ->options([
                        'recaptcha_v2' => [
                            'label' => 'reCAPTCHA v2',
                            'icon' => 'ph.bold.robot-bold',
                        ],
                        'recaptcha_v3' => [
                            'label' => 'reCAPTCHA v3',
                            'icon' => 'ph.bold.robot-bold',
                        ],
                        'hcaptcha' => [
                            'label' => 'hCaptcha',
                            'icon' => 'ph.bold.puzzle-piece-bold',
                        ],
                        'turnstile' => [
                            'label' => 'Turnstile',
                            'icon' => 'ph.bold.cloud-bold',
                        ],
                        'yandex' => [
                            'label' => 'Yandex SmartCaptcha',
                            'icon' => 'ph.bold.shield-check-bold',
                        ],
                    ])
                    ->value(config('auth.captcha.type'))
                    ->size('small'),
            )
                ->label(__('admin-main-settings.labels.captcha_type'))
                ->required(),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('recaptcha_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_site_key'))
                        ->value(config('auth.captcha.recaptcha.site_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_site_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_site_key')),
                LayoutFactory::field(
                    Input::make('recaptcha_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_secret_key'))
                        ->value(config('auth.captcha.recaptcha.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_secret_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_secret_key')),
            ])->setVisible($captchaType === 'recaptcha_v2'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('recaptcha_v3_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_v3_site_key'))
                        ->value(config('auth.captcha.recaptcha_v3.site_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_v3_site_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_v3_site_key')),
                LayoutFactory::field(
                    Input::make('recaptcha_v3_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_v3_secret_key'))
                        ->value(config('auth.captcha.recaptcha_v3.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_v3_secret_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_v3_secret_key')),
            ])->setVisible($captchaType === 'recaptcha_v3'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('recaptcha_v3_score_threshold')
                        ->type('number')
                        ->step('0.05')
                        ->min(0)
                        ->max(1)
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_v3_score_threshold'))
                        ->value(config('auth.captcha.recaptcha_v3.score_threshold', 0.5)),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_v3_score_threshold'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_v3_score_threshold')),
            ])->ratio('50/50')->setVisible($captchaType === 'recaptcha_v3'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('hcaptcha_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.hcaptcha_site_key'))
                        ->value(config('auth.captcha.hcaptcha.site_key')),
                )
                    ->label(__('admin-main-settings.labels.hcaptcha_site_key'))
                    ->popover(__('admin-main-settings.popovers.hcaptcha_site_key')),
                LayoutFactory::field(
                    Input::make('hcaptcha_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.hcaptcha_secret_key'))
                        ->value(config('auth.captcha.hcaptcha.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.hcaptcha_secret_key'))
                    ->popover(__('admin-main-settings.popovers.hcaptcha_secret_key')),
            ])->setVisible($captchaType === 'hcaptcha'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('turnstile_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.turnstile_site_key'))
                        ->value(config('auth.captcha.turnstile.site_key')),
                )
                    ->label(__('admin-main-settings.labels.turnstile_site_key'))
                    ->popover(__('admin-main-settings.popovers.turnstile_site_key')),
                LayoutFactory::field(
                    Input::make('turnstile_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.turnstile_secret_key'))
                        ->value(config('auth.captcha.turnstile.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.turnstile_secret_key'))
                    ->popover(__('admin-main-settings.popovers.turnstile_secret_key')),
            ])->setVisible($captchaType === 'turnstile'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('yandex_client_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.yandex_client_key'))
                        ->value(config('auth.captcha.yandex.client_key')),
                )
                    ->label(__('admin-main-settings.labels.yandex_client_key'))
                    ->popover(__('admin-main-settings.popovers.yandex_client_key')),
                LayoutFactory::field(
                    Input::make('yandex_server_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.yandex_server_key'))
                        ->value(config('auth.captcha.yandex.server_key')),
                )
                    ->label(__('admin-main-settings.labels.yandex_server_key'))
                    ->popover(__('admin-main-settings.popovers.yandex_server_key')),
            ])->setVisible($captchaType === 'yandex'),
        ])->title(__('admin-main-settings.blocks.captcha_settings'));
    }

    private function usersTwoFactorBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('two_factor_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.shield-check-bold'],
                        ])
                        ->value(config('auth.two_factor.enabled') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.two_factor_enabled'))
                    ->popover(__('admin-main-settings.popovers.two_factor_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('two_factor_force')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.two_factor.optional'),
                                'icon' => 'ph.bold.user-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.two_factor.required'),
                                'icon' => 'ph.bold.lock-bold',
                            ],
                        ])
                        ->value(config('auth.two_factor.force') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.two_factor_force'))
                    ->popover(__('admin-main-settings.popovers.two_factor_force')),
            ]),
            LayoutFactory::field(
                Input::make('two_factor_issuer')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.two_factor_issuer'))
                    ->value(config('auth.two_factor.issuer', '')),
            )
                ->label(__('admin-main-settings.labels.two_factor_issuer'))
                ->popover(__('admin-main-settings.popovers.two_factor_issuer')),
        ])->title(__('admin-main-settings.blocks.two_factor_settings'));
    }

    private function usersProfileBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                ButtonGroup::make('change_uri')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.link-bold'],
                    ])
                    ->value(config('profile.change_uri') ? '1' : '0')
                    ->color('accent'),
            )->label(__('admin-main-settings.labels.change_uri')),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('default_avatar')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(asset(config('profile.default_avatar'))),
                )->label(__('admin-main-settings.labels.default_avatar')),
                LayoutFactory::field(
                    Input::make('default_banner')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(asset(config('profile.default_banner'))),
                )->label(__('admin-main-settings.labels.default_banner')),
            ]),
            LayoutFactory::rows([
                Button::make(__('admin-main-settings.buttons.save_profile_images'))
                    ->size('small')
                    ->type(Color::ACCENT)
                    ->method('saveProfileImages'),
            ]),
        ])->title(__('admin-main-settings.blocks.profile_settings'));
    }

    private function usersProfileTabsOrderBlock()
    {
        if ($this->profileTabs->isEmpty()) {
            return LayoutFactory::block([
                LayoutFactory::view('admin-main-settings::profile-tabs-empty'),
            ])->title(__('admin-main-settings.blocks.profile_tabs_order'));
        }

        return LayoutFactory::sortable('profileTabs', [
            Sight::make('title', __('admin-main-settings.labels.profile_tab_title'))->render(static function ($tab) {
                $icon = $tab->icon;
                $title = $tab->title;
                $path = $tab->path;

                return view('admin-main-settings::cells.profile-tab-item', compact('icon', 'title', 'path'));
            }),
        ])
            ->title(__('admin-main-settings.blocks.profile_tabs_order'))
            ->description(__('admin-main-settings.blocks.profile_tabs_order_description'))
            ->maxLevels(1)
            ->onSortEnd('saveProfileTabsOrder');
    }

    private function mailSettingsLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::split([
                    LayoutFactory::field(Toggle::make('smtp')->checked(config('mail.smtp')))->label(__(
                        'admin-main-settings.labels.smtp',
                    )),
                    LayoutFactory::field(
                        Input::make('host')
                            ->type('text')
                            ->placeholder(__('admin-main-settings.placeholders.smtp_host'))
                            ->value(config('mail.host')),
                    )->label(__('admin-main-settings.labels.host')),
                ])->ratio('40/60'),
                LayoutFactory::field(
                    Input::make('port')
                        ->type('number')
                        ->placeholder(__('admin-main-settings.placeholders.smtp_port'))
                        ->value(config('mail.port')),
                )->label(__('admin-main-settings.labels.port')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('username')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.username'))
                        ->value(config('mail.username')),
                )->label(__('admin-main-settings.labels.username')),
                LayoutFactory::field(
                    Input::make('password')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.password'))
                        ->value(config('mail.password')),
                )->label(__('admin-main-settings.labels.password')),
                LayoutFactory::field(
                    ButtonGroup::make('secure')
                        ->options([
                            'tls' => [
                                'label' => 'TLS',
                                'icon' => 'ph.bold.lock-bold',
                            ],
                            'ssl' => [
                                'label' => 'SSL',
                                'icon' => 'ph.bold.shield-check-bold',
                            ],
                        ])
                        ->value(config('mail.secure'))
                        ->color('accent'),
                )->label(__('admin-main-settings.labels.secure')),
                LayoutFactory::field(
                    Input::make('from')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.from'))
                        ->value(config('mail.from')),
                )
                    ->label(__('admin-main-settings.labels.from'))
                    ->popover(__('admin-main-settings.popovers.from'))
                    ->small(__('admin-main-settings.examples.from')),
            ]),
            LayoutFactory::rows([
                Button::make(__('admin-main-settings.buttons.test_mail'))
                    ->size('small')
                    ->type(Color::ACCENT)
                    ->method('testMail'),
            ]),
        ])->title(__('admin-main-settings.blocks.mail_settings'));
    }

    private function localizationSettingsLayout()
    {
        return LayoutFactory::columns([
            LayoutFactory::block([
                LayoutFactory::field(
                    Select::make('locale')
                        ->placeholder(__('admin-main-settings.placeholders.locale'))
                        ->value(config('lang.locale'))
                        ->aligned()
                        ->options(array_combine(
                            config('lang.available'),
                            array_map(static fn($key) => __('langs.' . $key), config('lang.available')),
                        )),
                )->label(__('admin-main-settings.labels.locale')),
            ])->title(__('admin-main-settings.blocks.localization_settings')),
            LayoutFactory::block([
                LayoutFactory::view('admin-main-settings::languages', [
                    'languages' => config('lang.all'),
                    'available' => config('lang.available'),
                ]),
            ])->title(__('admin-main-settings.blocks.active_languages'))->description(__(
                'admin-main-settings.blocks.active_languages_description',
            )),
        ]);
    }

    private function advancedSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.performance'))
                ->icon('ph.bold.lightning-bold')
                ->layouts([
                    $this->advancedPerformanceBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.debug_settings'))
                ->icon('ph.bold.bug-bold')
                ->layouts([
                    $this->advancedDebugBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.misc_settings'))
                ->icon('ph.bold.dots-three-bold')
                ->layouts([
                    $this->additionalSettingsGeneralBlock(),
                ]),
        ])
            ->slug('advanced_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function additionalSettingsGeneralBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('share')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.share-network-bold'],
                        ])
                        ->value(config('app.share') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.share'))
                    ->small(__('admin-main-settings.labels.share_description')),
                LayoutFactory::field(
                    ButtonGroup::make('flute_copyright')
                        ->options([
                            '0' => ['label' => __('def.hide'), 'icon' => 'ph.bold.eye-slash-bold'],
                            '1' => ['label' => __('def.show'), 'icon' => 'ph.bold.eye-bold'],
                        ])
                        ->value(config('app.flute_copyright') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.copyright'))
                    ->small(__('admin-main-settings.labels.copyright_description')),
                LayoutFactory::field(
                    ButtonGroup::make('discord_link_roles')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.discord-logo-bold'],
                        ])
                        ->value(config('app.discord_link_roles') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.discord_link_roles'))
                    ->small(__('admin-main-settings.labels.discord_link_roles_description')),
            ]),
        ])->title(__('admin-main-settings.blocks.misc_settings'))->addClass('mb-3');
    }

    private function additionalSettingsImagesBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('logo')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp, image/svg+xml')
                        ->defaultFile(!str_ends_with(config('app.logo'), '.svg') ? asset(config('app.logo')) : null),
                )->label(__('admin-main-settings.labels.logo')),
                LayoutFactory::field(
                    Input::make('logo_light')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp, image/svg+xml')
                        ->defaultFile(
                            !str_ends_with(config('app.logo_light', ''), '.svg')
                                ? asset(config('app.logo_light', ''))
                                : null,
                        ),
                )->label(__('admin-main-settings.labels.logo_light')),
                LayoutFactory::field(
                    Input::make('bg_image')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(asset(config('app.bg_image'))),
                )
                    ->label(__('admin-main-settings.labels.bg_image'))
                    ->small(__('admin-main-settings.examples.bg_image')),
                LayoutFactory::field(
                    Input::make('bg_image_light')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(asset(config('app.bg_image_light', ''))),
                )
                    ->label(__('admin-main-settings.labels.bg_image_light'))
                    ->small(__('admin-main-settings.examples.bg_image_light')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('favicon')
                        ->type('file')
                        ->filePond()
                        ->accept('image/x-icon, image/vnd.microsoft.icon, .ico')
                        ->defaultFile(asset('favicon.ico')),
                )->label(__('admin-main-settings.labels.favicon')),
                LayoutFactory::field(
                    Input::make('social_image')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png')
                        ->defaultFile(asset('assets/img/social-image.png')),
                )->label(__('admin-main-settings.labels.social_image')),
            ]),
            LayoutFactory::rows([
                Button::make(__('admin-main-settings.buttons.save_flute_images'))
                    ->size('small')
                    ->type(Color::ACCENT)
                    ->method('saveFluteImages'),
            ]),
        ])->title(__('admin-main-settings.blocks.image_settings'));
    }
}
