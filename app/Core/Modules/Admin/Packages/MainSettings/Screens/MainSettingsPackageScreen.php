<?php

namespace Flute\Admin\Packages\MainSettings\Screens;

use Exception;
use Flute\Admin\Packages\MainSettings\Layouts\DatabaseSettingsLayout;
use Flute\Admin\Packages\MainSettings\Services\MainSettingsPackageService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\RichText;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Services\EmailService;
use Flute\Core\Support\FileUploader;
use Flute\Core\Support\FluteStr;
use PDO;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    protected string $name = 'admin-main-settings.labels.main_settings';

    protected ?string $description = 'admin-main-settings.labels.main_settings_description';

    protected $permission = 'admin.system';

    protected MainSettingsPackageService $configService;

    public function mount(): void
    {
        breadcrumb()->add(__('admin-main-settings.breadcrumbs.admin_panel'), url('/admin'))
            ->add(__('admin-main-settings.tabs.main_settings'));

        $this->configService = app(MainSettingsPackageService::class);
        $this->databaseConnections = $this->configService->initDatabases();
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('admin-main-settings.buttons.clear_cache'))
                ->icon('ph.regular.cloud')
                ->method('clearCache')
                ->type(Color::OUTLINE_WARNING),

            Button::make(__('admin-main-settings.buttons.save'))
                ->method('save'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::tabs([
                Tab::make(__('admin-main-settings.tabs.main_settings'))
                    ->icon('ph.bold.gear-bold')
                    ->layouts([
                        $this->mainSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.databases'))
                    ->icon('ph.bold.cloud-bold')
                    ->layouts([
                        DatabaseSettingsLayout::class,
                    ]),
                Tab::make(__('admin-main-settings.tabs.users'))
                    ->icon('ph.bold.user-circle-bold')
                    ->layouts([
                        $this->usersSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.mail'))
                    ->icon('ph.bold.envelope-bold')
                    ->layouts([
                        $this->mailSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.localization'))
                    ->icon('ph.bold.translate-bold')
                    ->layouts([
                        $this->localizationSettingsLayout(),
                    ]),
                Tab::make(__('admin-main-settings.tabs.additional_settings'))
                    ->icon('ph.bold.gear-fine-bold')
                    ->layouts([
                        $this->additionalSettingsLayout(),
                    ]),
            ])
                ->slug('settings')
                ->pills(),
        ];
    }

    public function testMail()
    {
        try {
            $to = user()->email;
            $mail = config('mail');

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
        } catch (Exception $e) {
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
        $socialImageError = $this->processFixedFileReplace('social_image', BASE_PATH . '/public/assets/img/social-image.png');

        if ($avatarError || $logoLightError || $bannerError || $bannerLightError || $faviconError || $socialImageError) {
            $this->flashMessage($avatarError ?? $logoLightError ?? $bannerError ?? $bannerLightError ?? $faviconError ?? $socialImageError, 'error');

            return;
        }

        if (!isset($this->logo)) {
            config()->set('app.logo', 'assets/img/logo.svg');
        }

        if (!isset($this->logo_light)) {
            config()->set('app.logo_light', 'assets/img/logo-light.svg');
        }

        if (!isset($this->bg_image)) {
            config()->set('app.bg_image', '');
        }

        if (!isset($this->bg_image_light)) {
            config()->set('app.bg_image_light', '');
        }

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.flute_images_saved'), 'success');
        } catch (Exception $e) {
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

        $avatarError = $this->processProfileImageUpload('default_avatar', $uploader, $uploadsDir, 'profile.default_avatar', 'assets/img/no_avatar.webp');
        $bannerError = $this->processProfileImageUpload('default_banner', $uploader, $uploadsDir, 'profile.default_banner', 'assets/img/no_banner.webp');

        if ($avatarError || $bannerError) {
            $this->flashMessage($avatarError ?? $bannerError, 'error');

            return;
        }

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.profile_images_saved'), 'success');

            $this->clearCache();
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    public function save()
    {
        $currentTab = request()->input('tab-settings', FluteStr::slug(__('admin-main-settings.tabs.main_settings')));

        try {
            $save = $this->configService->saveSettings($currentTab, request()->input());

            if ($save) {
                // if (function_exists('opcache_reset')) {
                //     @opcache_reset();
                // }

                $this->flashMessage(__('admin-main-settings.messages.settings_saved_successfully'));
            }
        } catch (Exception $e) {
            $this->flashMessage(__('admin-main-settings.messages.settings_save_error') . $e->getMessage(), 'error');
        }
    }

    public function clearCache()
    {
        $cachePaths = [
            BASE_PATH . '/storage/app/cache/*',
            BASE_PATH . '/storage/app/views/*',
            BASE_PATH . '/storage/logs/*',
            BASE_PATH . '/storage/app/proxies/*',
            BASE_PATH . '/storage/app/translations/*',
            BASE_PATH . '/public/assets/css/cache/*',
            BASE_PATH . '/public/assets/js/cache/*',
        ];

        try {
            $filesystem = fs();

            foreach ($cachePaths as $path) {
                $files = glob($path);
                if ($files) {
                    $filesystem->remove($files);
                }
            }

            $this->clearOpcache();

            app(\Flute\Core\Database\DatabaseConnection::class)->forceRefreshSchema();

            // if (!$withoutMessage) {
            $this->flashMessage(__('admin-main-settings.messages.cache_cleared_successfully'));
            // }
        } catch (IOException $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function addDatabaseModal(Repository $parameters)
    {
        $defaultConnection = config('database.connections.default');

        $explode = explode('\\', $defaultConnection->driver);

        $driver = str_replace('driver', '', strtolower(end($explode)));

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('driver')
                    ->options([
                        'mysql' => 'MySQL',
                        'postgres' => 'PostgreSQL',
                    ])
                    ->value($driver)
                    ->placeholder(__('admin-main-settings.placeholders.db_driver'))
            )->label(__('admin-main-settings.labels.db_driver'))->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.database_name'))
            )->label(__('admin-main-settings.labels.database_name'))->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value($defaultConnection->connection->host)
                    ->placeholder(__('admin-main-settings.placeholders.db_host'))
            )->label(__('admin-main-settings.labels.host'))->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value($defaultConnection->connection->port)
                    ->placeholder(__('admin-main-settings.placeholders.db_port'))
            )->label(__('admin-main-settings.labels.port'))->required(),
            LayoutFactory::field(
                Input::make('user')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.db_user'))
            )->label(__('admin-main-settings.labels.user'))->required(),
            LayoutFactory::field(
                Input::make('database')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.db_database'))
            )->label(__('admin-main-settings.labels.database'))->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.db_password'))
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                Input::make('prefix')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.db_prefix'))
            )->label(__('admin-main-settings.labels.prefix'))->popover(__('admin-main-settings.popovers.prefix'))->small(__('admin-main-settings.examples.prefix')),
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

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('driver')
                    ->options([
                        'mysql' => 'MySQL',
                        'postgres' => 'PostgreSQL',
                    ])
                    ->value($driver)
                    ->placeholder(__('admin-main-settings.placeholders.db_driver'))
            )->label(__('admin-main-settings.labels.db_driver'))->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->value($databaseId)
                    ->placeholder(__('admin-main-settings.placeholders.database_name'))
                    ->readonly()
                    ->required()
            )->label(__('admin-main-settings.labels.database_name'))->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value($tcpConnection->host)
                    ->placeholder(__('admin-main-settings.placeholders.db_host'))
            )->label(__('admin-main-settings.labels.host'))->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value($tcpConnection->port)
                    ->placeholder(__('admin-main-settings.placeholders.db_port'))
            )->label(__('admin-main-settings.labels.port'))->required(),
            LayoutFactory::field(
                Input::make('user')
                    ->type('text')
                    ->value($tcpConnection->user)
                    ->placeholder(__('admin-main-settings.placeholders.db_user'))
            )->label(__('admin-main-settings.labels.user'))->required(),
            LayoutFactory::field(
                Input::make('database')
                    ->type('text')
                    ->value($tcpConnection->database)
                    ->placeholder(__('admin-main-settings.placeholders.db_database'))
            )->label(__('admin-main-settings.labels.database'))->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->value($tcpConnection->password)
                    ->placeholder(__('admin-main-settings.placeholders.db_password'))
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                Input::make('prefix')
                    ->type('text')
                    ->value($database['prefix'])
                    ->placeholder(__('admin-main-settings.placeholders.db_prefix'))
            )->label(__('admin-main-settings.labels.prefix'))->popover(__('admin-main-settings.popovers.prefix'))->small(__('admin-main-settings.examples.prefix')),
        ])
            ->method('changeDatabase')
            ->title(__('admin-main-settings.modals.edit_database', ['db' => $databaseId]))
            ->applyButton(__('admin-main-settings.buttons.save'))
            ->right();
    }

    public function addDatabase()
    {
        $data = request()->input();

        if (
            !$this->validate([
                'driver' => ['required', 'string', 'in:mysql,postgres'],
                'databaseName' => ['required', 'string', 'not-in:default'],
                'host' => ['required', 'string'],
                'port' => ['required', 'integer', 'min:1', 'max:65535'],
                'user' => ['required', 'string'],
                'database' => ['required', 'string'],
                'password' => ['nullable', 'string'],
                'prefix' => ['nullable', 'string'],
            ], request()->input())
        ) {
            return;
        }

        $connectionTest = $this->configService->testDatabaseConnection(
            $data['driver'],
            $data['host'],
            (int) $data['port'],
            $data['database'],
            $data['user'],
            $data['password'] ?? null
        );

        if ($connectionTest !== true) {
            $this->flashMessage(__('admin-main-settings.messages.connection_test_failed') . ": " . $connectionTest, 'error');

            return;
        }

        $databaseName = $data['databaseName'];
        $driver = $data['driver'];

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
            $connectionConfig = new \Cycle\Database\Config\MySQLDriverConfig(
                connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'],
                ),
                timezone: 'Asia/Yekaterinburg',
                queryCache: true,
            );
        } elseif ($driver === 'postgres') {
            $connectionConfig = new \Cycle\Database\Config\PostgresDriverConfig(
                connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                ),
                schema: 'public',
                queryCache: true,
            );
        } else {
            $this->flashMessage(__('admin-main-settings.messages.unsupported_driver'), 'error');

            return;
        }

        config()->set("database.connections.{$databaseName}", $connectionConfig);

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.add_database_success'));
            $this->databaseConnections = $this->configService->initDatabases();

            $this->closeModal();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-main-settings.messages.add_database_error') . $e->getMessage(), 'error');
        }
    }

    public function changeDatabase()
    {
        $data = request()->input();

        if (
            !$this->validate([
                'driver' => ['required', 'string', 'in:mysql,postgres'],
                'databaseName' => ['required', 'string', 'not-in:default'],
                'host' => ['required', 'string'],
                'port' => ['required', 'integer', 'min:1', 'max:65535'],
                'user' => ['required', 'string'],
                'database' => ['required', 'string'],
                'password' => ['nullable', 'string'],
                'prefix' => ['nullable', 'string'],
            ], request()->input())
        ) {
            return;
        }

        $connectionTest = $this->configService->testDatabaseConnection(
            $data['driver'],
            $data['host'],
            (int) $data['port'],
            $data['database'],
            $data['user'],
            $data['password'] ?? null
        );

        if ($connectionTest !== true) {
            $this->flashMessage(__('admin-main-settings.messages.connection_test_failed') . ": " . $connectionTest, 'error');

            return;
        }

        $databaseName = $data['databaseName'];
        $driver = $data['driver'];

        $databases = config('database.databases');
        if (!isset($databases[$databaseName])) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        config()->set("database.databases.{$databaseName}.prefix", $data['prefix'] ?? '');

        if ($driver === 'mysql') {
            $connectionConfig = new \Cycle\Database\Config\MySQLDriverConfig(
                connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'],
                ),
                timezone: 'Asia/Yekaterinburg',
                queryCache: true,
            );
        } elseif ($driver === 'postgres') {
            $connectionConfig = new \Cycle\Database\Config\PostgresDriverConfig(
                connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                ),
                schema: 'public',
                queryCache: true,
            );
        } else {
            $this->flashMessage(__('admin-main-settings.messages.unsupported_driver'), 'error');

            return;
        }

        config()->set("database.connections.{$databaseName}", $connectionConfig);

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.edit_database_success'));
            $this->closeModal();
            $this->databaseConnections = $this->configService->initDatabases();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-main-settings.messages.edit_database_error') . $e->getMessage(), 'error');
        }
    }

    public function removeDatabase()
    {
        $data = request()->input();

        if (
            !$this->validate([
                'databaseId' => ['required', 'string', 'not-in:default'],
            ], request()->input())
        ) {
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

        config()->set("database.databases", $databases);
        config()->set("database.connections", $connections);

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.remove_database_success'));
            $this->databaseConnections = $this->configService->initDatabases();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-main-settings.messages.remove_database_error'), 'error');
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
            'logo' => $this->logo
                ? 'image|max-file-size:10240'
                : 'nullable|image|max-file-size:10240',
            'logo_light' => $this->logo_light
                ? 'image|max-file-size:10240'
                : 'nullable|image|max-file-size:10240',
            'bg_image' => $this->bg_image
                ? 'image|max-file-size:10240'
                : 'nullable|image|max-file-size:10240',
            'bg_image_light' => $this->bg_image_light
                ? 'image|max-file-size:10240'
                : 'nullable|image|max-file-size:10240',
            'favicon' => $this->favicon
                ? 'mimes:ico|max-file-size:2048'
                : 'nullable|mimes:ico|max-file-size:2048',
            'social_image' => $this->social_image
                ? 'image|mimes:png|max-file-size:10240'
                : 'nullable|image|mimes:png|max-file-size:10240',
        ];

        return $this->validate($rules);
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
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    /**
     * Replace a target file in public path with the uploaded file, using a fixed filename.
     */
    protected function processFixedFileReplace(string $field, string $absoluteTargetPath): ?string
    {
        $file = $this->$field;
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
            } catch (Exception $e) {
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
    protected function processProfileImageUpload(string $field, FileUploader $uploader, string $uploadsDir, string $configKey, string $defaultFile): ?string
    {
        $file = $this->$field;
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $newFile = $uploader->uploadImage($file, 10);

                if ($newFile === null) {
                    throw new RuntimeException(__('admin-main-settings.messages.upload_failed', ['field' => $field]));
                }

                config()->set($configKey, $newFile);

                return null;
            } catch (Exception $e) {
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
        return LayoutFactory::split([
            LayoutFactory::blank([
                LayoutFactory::block([
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Input::make('name')
                                ->type('text')
                                ->placeholder(__('admin-main-settings.placeholders.site_name'))
                                ->value(config('app.name'))
                        )->label(__('admin-main-settings.labels.site_name'))->required(),
                        LayoutFactory::field(
                            Input::make('url')
                                ->type('text')
                                ->placeholder(__('admin-main-settings.placeholders.site_url'))
                                ->value(config('app.url'))
                        )->label(__('admin-main-settings.labels.site_url'))->required(),
                    ]),
                    LayoutFactory::field(
                        Input::make('timezone')
                            ->placeholder(__('admin-main-settings.placeholders.timezone'))
                            ->value(config('app.timezone'))
                    )->label(__('admin-main-settings.labels.timezone'))->required()->small(__('admin-main-settings.examples.timezone')),
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Toggle::make('change_theme')
                                ->checked(config('app.change_theme'))
                        )->addClass('mt-2')->label(__('admin-main-settings.labels.change_theme'))->popover(__('admin-main-settings.popovers.change_theme')),
                        LayoutFactory::field(
                            Select::make('default_theme')
                                ->options([
                                    'dark' => __('admin-main-settings.options.theme.dark'),
                                    'light' => __('admin-main-settings.options.theme.light'),
                                ])
                                ->value(config('app.default_theme', 'dark'))
                        )->label(__('admin-main-settings.labels.default_theme'))->popover(__('admin-main-settings.popovers.default_theme')),
                    ])->ratio('50/50'),
                    LayoutFactory::field(
                        Input::make('flute_key')
                            ->type('password')
                            ->placeholder(__('admin-main-settings.placeholders.flute_key'))
                            ->value(config('app.flute_key'))
                    )->label(__('admin-main-settings.labels.flute_key'))->popover(__('admin-main-settings.popovers.flute_key')),
                    LayoutFactory::field(
                        Input::make('steam_api')
                            ->type('password')
                            ->placeholder(__('admin-main-settings.placeholders.steam_api'))
                            ->value(config('app.steam_api'))
                    )->label(__('admin-main-settings.labels.steam_api'))->popover(__('admin-main-settings.popovers.steam_api')),
                    LayoutFactory::field(
                        Input::make('steam_cache_duration')
                            ->type('number')
                            ->placeholder(__('admin-main-settings.placeholders.steam_cache_duration'))
                            ->value(config('app.steam_cache_duration', 3600))
                    )->label(__('admin-main-settings.labels.steam_cache_duration'))->popover(__('admin-main-settings.popovers.steam_cache_duration'))->small(__('admin-main-settings.examples.steam_cache_duration')),
                    LayoutFactory::field(
                        TextArea::make('footer_description')
                            ->placeholder(__('admin-main-settings.placeholders.footer_description'))
                            ->value(config('app.footer_description'))
                    )->label(__('admin-main-settings.labels.footer_description')),
                    LayoutFactory::field(
                        RichText::make('footer_additional')
                            ->toolbarPreset('minimal')
                            ->height(100)
                            ->placeholder(__('admin-main-settings.placeholders.footer_additional'))
                            ->value(config('app.footer_additional'))
                    )->label(__('admin-main-settings.labels.footer_additional')),
                ])->title(__('admin-main-settings.blocks.main_settings'))->addClass('mb-2'),

                LayoutFactory::block([
                    LayoutFactory::field(
                        Toggle::make('maintenance_mode')
                            ->checked(config('app.maintenance_mode'))
                    )->label(__('admin-main-settings.labels.maintenance_mode'))->popover(__('admin-main-settings.popovers.maintenance_mode')),
                    LayoutFactory::field(
                        TextArea::make('maintenance_message')
                            ->placeholder(__('admin-main-settings.placeholders.maintenance_message'))
                            ->value(config('app.maintenance_message'))
                    )->label(__('admin-main-settings.labels.maintenance_message')),
                ])->title(__('admin-main-settings.blocks.tech_work_settings'))->addClass('mb-2'),
            ]),

            LayoutFactory::blank([
                LayoutFactory::block([
                    LayoutFactory::field(
                        Input::make('keywords')
                            ->placeholder(__('admin-main-settings.placeholders.keywords'))
                            ->value(config('app.keywords'))
                    )->label(__('admin-main-settings.labels.keywords'))->required()->small(__('admin-main-settings.examples.keywords')),

                    LayoutFactory::field(
                        Select::make('robots')
                            ->value(config('app.robots', 'index, follow'))
                            ->options([
                                'index, follow' => __('admin-main-settings.options.robots.index_follow'),
                                'index, nofollow' => __('admin-main-settings.options.robots.index_nofollow'),
                                'noindex, nofollow' => __('admin-main-settings.options.robots.noindex_nofollow'),
                                'noindex, follow' => __('admin-main-settings.options.robots.noindex_follow'),
                            ])
                    )->label(__('admin-main-settings.labels.robots'))->required()->small(__('admin-main-settings.examples.robots')),

                    // description
                    LayoutFactory::field(
                        Input::make('description')
                            ->placeholder(__('admin-main-settings.placeholders.description'))
                            ->value(config('app.description'))
                    )->label(__('admin-main-settings.labels.description')),

                ])->title(__('admin-main-settings.blocks.seo'))->addClass('mb-2')->popover(__('admin-main-settings.popovers.seo')),

                LayoutFactory::block([
                    LayoutFactory::field(
                        Toggle::make('development_mode')
                            ->checked((bool) config('app.development_mode'))
                    )->label(__('admin-main-settings.labels.development_mode'))->popover(__('admin-main-settings.popovers.development_mode')),
                ])->title(__('admin-main-settings.blocks.development_settings'))->addClass('mb-2'),

                LayoutFactory::block([
                    LayoutFactory::columns([
                        LayoutFactory::field(
                            Toggle::make('is_performance')
                                ->checked(config('app.is_performance'))
                        )->label(__('admin-main-settings.labels.is_performance'))->popover(__('admin-main-settings.popovers.is_performance')),
                        LayoutFactory::field(
                            Toggle::make('cron_mode')
                                ->checked(config('app.cron_mode'))
                        )->label(__('admin-main-settings.labels.cron_mode'))->popover(__('admin-main-settings.popovers.cron_mode')),
                    ]),
                    LayoutFactory::view('admin-main-settings::cron')->setVisible(boolval(config('app.cron_mode'))),
                    LayoutFactory::columns([
                        LayoutFactory::field(
                            Toggle::make('csrf_enabled')
                                ->checked(config('app.csrf_enabled'))
                        )->label(__('admin-main-settings.labels.csrf_enabled')),
                        LayoutFactory::field(
                            Toggle::make('convert_to_webp')
                                ->checked(config('app.convert_to_webp'))
                        )->label(__('admin-main-settings.labels.convert_to_webp'))->popover(__('admin-main-settings.popovers.convert_to_webp')),
                    ]),
                    LayoutFactory::columns([
                        LayoutFactory::field(
                            Toggle::make('create_backup')
                                ->checked(config('app.create_backup', false))
                        )->label(__('admin-main-settings.labels.create_backup'))->popover(__('admin-main-settings.popovers.create_backup')),
                        LayoutFactory::field(
                            Toggle::make('auto_update')
                                ->checked(config('app.auto_update', false))
                        )->label(__('admin-main-settings.labels.auto_update'))
                            ->setVisible(config('app.cron_mode'))
                            ->popover(__('admin-main-settings.popovers.auto_update')),
                    ]),
                ])->title(__('admin-main-settings.blocks.optimization_security'))->addClass('mb-2')->description(__('admin-main-settings.blocks.optimization_security_description')),

                LayoutFactory::block([
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Toggle::make('debug')
                                ->checked(is_development() ? true : config('app.debug'))
                                ->disabled(is_development())
                        )->label(__('admin-main-settings.labels.debug'))->popover(__('admin-main-settings.popovers.debug')),
                    ])->ratio('50/50'),
                    LayoutFactory::field(
                        Input::make('debug_ips')
                            ->type('text')
                            ->placeholder(__('admin-main-settings.placeholders.debug_ips'))
                            ->value(is_array(config('app.debug_ips')) ? implode(', ', config('app.debug_ips')) : '')
                    )->label(__('admin-main-settings.labels.debug_ips'))->popover(__('admin-main-settings.popovers.debug_ips'))->small(__('admin-main-settings.examples.debug_ips')),
                ])->title(__('admin-main-settings.blocks.debug_settings'))->addClass('mb-2'),

                LayoutFactory::block([
                    LayoutFactory::field(
                        Input::make('currency_view')
                            ->type('text')
                            ->placeholder(__('admin-main-settings.placeholders.currency_view'))
                            ->value(config('lk.currency_view'))
                    )->label(__('admin-main-settings.labels.currency_view'))->popover(__('admin-main-settings.popovers.currency_view')),
                    LayoutFactory::field(
                        Toggle::make('oferta_view')
                            ->checked(config('lk.oferta_view'))
                    )->label(__('admin-main-settings.labels.oferta_view')),
                    // only modal
                    LayoutFactory::field(
                        Toggle::make('only_modal')
                            ->checked(config('lk.only_modal'))
                    )->label(__('admin-main-settings.labels.lk_only_modal'))->popover(__('admin-main-settings.popovers.lk_only_modal')),
                    LayoutFactory::field(
                        Input::make('oferta_url')
                            ->type('text')
                            ->placeholder(__('admin-main-settings.placeholders.oferta_url'))
                            ->value(config('lk.oferta_url'))
                    )->label(__('admin-main-settings.labels.oferta_url'))->popover(__('admin-main-settings.popovers.oferta_url'))->small(__('admin-main-settings.examples.oferta_url')),
                ])->title(__('admin-main-settings.blocks.personal_cabinet_settings'))->addClass('mb-2'),
            ]),
        ])->ratio('50/50');
    }

    private function usersSettingsLayout()
    {
        return LayoutFactory::split([
            LayoutFactory::blank([
                LayoutFactory::block([
                    LayoutFactory::columns([
                        LayoutFactory::field(
                            Toggle::make('reset_password')
                                ->checked(config('auth.reset_password'))
                        )->label(__('admin-main-settings.labels.reset_password'))->popover(__('admin-main-settings.popovers.reset_password')),
                        LayoutFactory::field(
                            Toggle::make('only_social')
                                ->checked(config('auth.only_social'))
                        )->label(__('admin-main-settings.labels.only_social'))->popover(__('admin-main-settings.popovers.only_social')),
                    ]),
                    LayoutFactory::columns([
                        LayoutFactory::field(
                            Toggle::make('only_modal')
                                ->checked(config('auth.only_modal'))
                        )->label(__('admin-main-settings.labels.only_modal'))->popover(__('admin-main-settings.popovers.only_modal')),
                        LayoutFactory::field(
                            Toggle::make('confirm_email')
                                ->checked(config('auth.registration.confirm_email'))
                        )->label(__('admin-main-settings.labels.confirm_email'))->popover(__('admin-main-settings.popovers.confirm_email')),
                    ]),
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Toggle::make('remember_me')
                                ->checked(config('auth.remember_me'))
                        )->label(__('admin-main-settings.labels.remember_me'))->popover(__('admin-main-settings.popovers.remember_me')),
                        LayoutFactory::field(
                            Input::make('remember_me_duration')
                                ->type('number')
                                ->placeholder(__('admin-main-settings.placeholders.remember_me_duration'))
                                ->value(config('auth.remember_me_duration'))
                        )->label(__('admin-main-settings.labels.remember_me_duration'))->small(__('admin-main-settings.examples.remember_me_duration')),
                    ]),
                    LayoutFactory::field(
                        Select::make('default_role')
                            ->fromDatabase('roles', 'name', 'id', ['name', 'id'])
                            ->placeholder(__('admin-main-settings.placeholders.default_role_placeholder'))
                            ->value(config('auth.default_role', 0))
                    )->label(__('admin-main-settings.labels.default_role'))->popover(__('admin-main-settings.popovers.default_role')),
                ])->title(__('admin-main-settings.blocks.auth_settings'))->addClass('mb-3'),

                LayoutFactory::block([
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Toggle::make('check_ip')
                                ->checked(config('auth.check_ip'))
                        )->label(__('admin-main-settings.labels.check_ip'))->popover(__('admin-main-settings.popovers.check_ip')),
                        LayoutFactory::field(
                            Toggle::make('security_token')
                                ->checked(config('auth.security_token'))
                        )->label(__('admin-main-settings.labels.security_token'))->popover(__('admin-main-settings.popovers.security_token')),
                    ]),
                ])->title(__('admin-main-settings.blocks.session_settings'))->description(__('admin-main-settings.blocks.session_description')),

                LayoutFactory::block([
                    LayoutFactory::columns([
                        LayoutFactory::field(
                            Toggle::make('captcha_enabled_login')
                                ->checked(boolval(config('auth.captcha.enabled.login')))
                        )->label(__('admin-main-settings.labels.captcha_enabled_login'))->popover(__('admin-main-settings.popovers.captcha_enabled_login')),
                        LayoutFactory::field(
                            Toggle::make('captcha_enabled_register')
                                ->checked(boolval(config('auth.captcha.enabled.register')))
                        )->label(__('admin-main-settings.labels.captcha_enabled_register'))->popover(__('admin-main-settings.popovers.captcha_enabled_register')),
                    ]),
                    LayoutFactory::field(
                        Toggle::make('captcha_enabled_password_reset')
                            ->checked(boolval(config('auth.captcha.enabled.password_reset')))
                    )->label(__('admin-main-settings.labels.captcha_enabled_password_reset'))->popover(__('admin-main-settings.popovers.captcha_enabled_password_reset')),
                    LayoutFactory::field(
                        Select::make('captcha_type')
                            ->options([
                                'recaptcha_v2' => 'reCAPTCHA v2',
                                'hcaptcha' => 'hCaptcha',
                            ])
                            ->value(config('auth.captcha.type'))
                    )->label(__('admin-main-settings.labels.captcha_type'))->required(),
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Input::make('recaptcha_site_key')
                                ->type('text')
                                ->placeholder(__('admin-main-settings.placeholders.recaptcha_site_key'))
                                ->value(config('auth.captcha.recaptcha.site_key'))
                        )->label(__('admin-main-settings.labels.recaptcha_site_key'))->popover(__('admin-main-settings.popovers.recaptcha_site_key')),
                        LayoutFactory::field(
                            Input::make('recaptcha_secret_key')
                                ->type('password')
                                ->placeholder(__('admin-main-settings.placeholders.recaptcha_secret_key'))
                                ->value(config('auth.captcha.recaptcha.secret_key'))
                        )->label(__('admin-main-settings.labels.recaptcha_secret_key'))->popover(__('admin-main-settings.popovers.recaptcha_secret_key')),
                    ]),
                    LayoutFactory::split([
                        LayoutFactory::field(
                            Input::make('hcaptcha_site_key')
                                ->type('text')
                                ->placeholder(__('admin-main-settings.placeholders.hcaptcha_site_key'))
                                ->value(config('auth.captcha.hcaptcha.site_key'))
                        )->label(__('admin-main-settings.labels.hcaptcha_site_key'))->popover(__('admin-main-settings.popovers.hcaptcha_site_key')),
                        LayoutFactory::field(
                            Input::make('hcaptcha_secret_key')
                                ->type('password')
                                ->placeholder(__('admin-main-settings.placeholders.hcaptcha_secret_key'))
                                ->value(config('auth.captcha.hcaptcha.secret_key'))
                        )->label(__('admin-main-settings.labels.hcaptcha_secret_key'))->popover(__('admin-main-settings.popovers.hcaptcha_secret_key')),
                    ]),
                ])->title(__('admin-main-settings.blocks.captcha_settings')),
            ]),
            LayoutFactory::block([
                LayoutFactory::field(
                    Toggle::make('change_uri')
                        ->checked(config('profile.change_uri'))
                )->label(__('admin-main-settings.labels.change_uri')),
                LayoutFactory::split([
                    LayoutFactory::field(
                        Input::make('default_avatar')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset(config('profile.default_avatar')))
                    )->label(__('admin-main-settings.labels.default_avatar')),
                    LayoutFactory::field(
                        Input::make('default_banner')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset(config('profile.default_banner')))
                    )->label(__('admin-main-settings.labels.default_banner')),
                ]),
                LayoutFactory::rows([
                    Button::make(__('admin-main-settings.buttons.save_profile_images'))
                        ->size('small')
                        ->type(Color::ACCENT)
                        ->method('saveProfileImages'),
                ]),
            ])->title(__('admin-main-settings.blocks.profile_settings')),
        ])->ratio('50/50');
    }

    private function mailSettingsLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::split([
                    LayoutFactory::field(
                        Toggle::make('smtp')
                            ->checked(config('mail.smtp'))
                    )->label(__('admin-main-settings.labels.smtp')),
                    LayoutFactory::field(
                        Input::make('host')
                            ->type('text')
                            ->placeholder(__('admin-main-settings.placeholders.smtp_host'))
                            ->value(config('mail.host'))
                    )->label(__('admin-main-settings.labels.host')),
                ])->ratio('40/60'),
                LayoutFactory::field(
                    Input::make('port')
                        ->type('number')
                        ->placeholder(__('admin-main-settings.placeholders.smtp_port'))
                        ->value(config('mail.port'))
                )->label(__('admin-main-settings.labels.port')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('username')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.username'))
                        ->value(config('mail.username'))
                )->label(__('admin-main-settings.labels.username')),
                LayoutFactory::field(
                    Input::make('password')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.password'))
                        ->value(config('mail.password'))
                )->label(__('admin-main-settings.labels.password')),
                LayoutFactory::field(
                    Select::make('secure')
                        ->options([
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                        ])
                        ->value(config('mail.secure'))
                        ->placeholder(__('admin-main-settings.placeholders.secure'))
                )->label(__('admin-main-settings.labels.secure')),
                LayoutFactory::field(
                    Input::make('from')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.from'))
                        ->value(config('mail.from'))
                )->label(__('admin-main-settings.labels.from'))->popover(__('admin-main-settings.popovers.from'))->small(__('admin-main-settings.examples.from')),
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
                        ->options(array_combine(
                            config('lang.available'),
                            array_map(
                                static fn ($key) => __('langs.' . $key),
                                config('lang.available')
                            )
                        ))
                )->label(__('admin-main-settings.labels.locale')),
            ])->title(__('admin-main-settings.blocks.localization_settings')),
            LayoutFactory::block([
                LayoutFactory::split(
                    array_map(
                        static fn ($lang) => LayoutFactory::field(
                            Toggle::make("available[{$lang}]")
                                ->checked(in_array($lang, config('lang.available')))
                        )->label(__('langs.' . $lang)),
                        config('lang.all')
                    )
                )->ratio('50/50'),
            ])->title(__('admin-main-settings.blocks.active_languages'))->description(__('admin-main-settings.blocks.active_languages_description')),
        ]);
    }

    private function additionalSettingsLayout()
    {
        return [
            LayoutFactory::block([
                LayoutFactory::columns([
                    LayoutFactory::field(
                        Toggle::make('share')
                            ->checked(config('app.share'))
                    )->label(__('admin-main-settings.labels.share'))->small(__('admin-main-settings.labels.share_description')),
                    LayoutFactory::field(
                        Toggle::make('flute_copyright')
                            ->checked(config('app.flute_copyright'))
                    )->label(__('admin-main-settings.labels.copyright'))->small(__('admin-main-settings.labels.copyright_description')),
                ]),
                LayoutFactory::columns([
                    LayoutFactory::field(
                        Toggle::make('discord_link_roles')
                            ->checked(config('app.discord_link_roles'))
                    )->label(__('admin-main-settings.labels.discord_link_roles'))->small(__('admin-main-settings.labels.discord_link_roles_description')),
                    LayoutFactory::field(
                        Toggle::make('minify')
                            ->checked(config('assets.minify'))
                    )->label(__('admin-main-settings.labels.minify'))->small(__('admin-main-settings.labels.minify_description')),
                    LayoutFactory::field(
                        Toggle::make('autoprefix')
                            ->checked(config('assets.autoprefix', false))
                    )->label(__('admin-main-settings.labels.autoprefix'))->small(__('admin-main-settings.labels.autoprefix_description')),
                ]),
            ])->addClass('mb-3'),

            LayoutFactory::block([
                LayoutFactory::columns([
                    LayoutFactory::field(
                        Input::make('logo')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp, image/svg+xml')
                            ->defaultFile(!str_ends_with(config('app.logo'), '.svg') ? asset(config('app.logo')) : null)
                    )->label(__('admin-main-settings.labels.logo')),
                    LayoutFactory::field(
                        Input::make('logo_light')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp, image/svg+xml')
                            ->defaultFile(!str_ends_with(config('app.logo_light', ''), '.svg') ? asset(config('app.logo_light', '')) : null)
                    )->label(__('admin-main-settings.labels.logo_light')),
                    LayoutFactory::field(
                        Input::make('bg_image')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset(config('app.bg_image')))
                    )->label(__('admin-main-settings.labels.bg_image'))->small(__('admin-main-settings.examples.bg_image')),
                    LayoutFactory::field(
                        Input::make('bg_image_light')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png, image/jpeg, image/gif, image/webp')
                            ->defaultFile(asset(config('app.bg_image_light', '')))
                    )->label(__('admin-main-settings.labels.bg_image_light'))->small(__('admin-main-settings.examples.bg_image_light')),
                ]),
                LayoutFactory::columns([
                    LayoutFactory::field(
                        Input::make('favicon')
                            ->type('file')
                            ->filePond()
                            // Accept common ico MIME types and extension for better browser compatibility
                            ->accept('image/x-icon, image/vnd.microsoft.icon, .ico')
                            ->defaultFile(asset('favicon.ico'))
                    )->label(__('admin-main-settings.labels.favicon')),
                    LayoutFactory::field(
                        Input::make('social_image')
                            ->type('file')
                            ->filePond()
                            ->accept('image/png')
                            ->defaultFile(asset('assets/img/social-image.png'))
                    )->label(__('admin-main-settings.labels.social_image')),
                ]),
                LayoutFactory::rows([
                    Button::make(__('admin-main-settings.buttons.save_flute_images'))
                        ->size('small')
                        ->type(Color::ACCENT)
                        ->method('saveFluteImages'),
                ]),
            ])->title(__('admin-main-settings.blocks.image_settings')),
        ];
    }
}
