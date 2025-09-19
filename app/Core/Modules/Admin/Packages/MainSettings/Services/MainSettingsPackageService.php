<?php

namespace Flute\Admin\Packages\MainSettings\Services;

use Exception;
use Flute\Admin\Platform\Repository;
use Flute\Core\Support\FluteStr;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PDO;
use PDOException;

class MainSettingsPackageService
{
    /**
     * Slaги вкладок для использования в разных методах.
     */
    protected array $tabSlugs;

    /**
     * Маппинг входных ключей к ключам конфигурации.
     */
    protected array $configMappings;

    public function __construct()
    {
        $this->tabSlugs = [
            'main_settings' => FluteStr::slug(__('admin-main-settings.tabs.main_settings')),
            'additional_settings' => FluteStr::slug(__('admin-main-settings.tabs.additional_settings')),
            'users' => FluteStr::slug(__('admin-main-settings.tabs.users')),
            'mail' => FluteStr::slug(__('admin-main-settings.tabs.mail')),
            'localization' => FluteStr::slug(__('admin-main-settings.tabs.localization')),
        ];

        $this->configMappings = [
            $this->tabSlugs['main_settings'] => [
                'name' => 'app.name',
                'url' => 'app.url',
                'timezone' => 'app.timezone',
                'steam_api' => 'app.steam_api',
                'steam_cache_duration' => 'app.steam_cache_duration',
                'footer_description' => 'app.footer_description',
                'footer_additional' => 'app.footer_additional',
                'create_backup' => 'app.create_backup',
                'maintenance_mode' => 'app.maintenance_mode',
                'maintenance_message' => 'app.maintenance_message',
                'is_performance' => 'app.is_performance',
                'cron_mode' => 'app.cron_mode',
                'auto_update' => 'app.auto_update',
                'csrf_enabled' => 'app.csrf_enabled',
                'convert_to_webp' => 'app.convert_to_webp',
                'development_mode' => 'app.development_mode',
                'debug' => 'app.debug',
                'debug_ips' => 'app.debug_ips',
                'currency_view' => 'lk.currency_view',
                'oferta_view' => 'lk.oferta_view',
                'oferta_url' => 'lk.oferta_url',
                'only_modal' => 'lk.only_modal',
                'flute_key' => 'app.flute_key',
                'keywords' => 'app.keywords',
                'description' => 'app.description',
                'robots' => 'app.robots',
                'change_theme' => 'app.change_theme',
                'default_theme' => 'app.default_theme',
            ],
            $this->tabSlugs['additional_settings'] => [
                'share' => 'app.share',
                'flute_copyright' => 'app.flute_copyright',
                'discord_link_roles' => 'app.discord_link_roles',
                'minify' => 'assets.minify',
                'logo' => 'app.logo',
                'bg_image' => 'app.bg_image',
                'bg_image_light' => 'app.bg_image_light',
            ],
            $this->tabSlugs['users'] => [
                'reset_password' => 'auth.reset_password',
                'only_social' => 'auth.only_social',
                'only_modal' => 'auth.only_modal',
                'confirm_email' => 'auth.registration.confirm_email',
                'remember_me' => 'auth.remember_me',
                'remember_me_duration' => 'auth.remember_me_duration',
                'check_ip' => 'auth.check_ip',
                'security_token' => 'auth.security_token',
                'change_uri' => 'profile.change_uri',
                'default_avatar' => 'profile.default_avatar',
                'default_banner' => 'profile.default_banner',
                'captcha_enabled_login' => 'auth.captcha.enabled.login',
                'captcha_enabled_register' => 'auth.captcha.enabled.register',
                'captcha_enabled_password_reset' => 'auth.captcha.enabled.password_reset',
                'captcha_type' => 'auth.captcha.type',
                'recaptcha_site_key' => 'auth.captcha.recaptcha.site_key',
                'recaptcha_secret_key' => 'auth.captcha.recaptcha.secret_key',
                'hcaptcha_site_key' => 'auth.captcha.hcaptcha.site_key',
                'hcaptcha_secret_key' => 'auth.captcha.hcaptcha.secret_key',
                'default_role' => 'auth.default_role',
            ],
            $this->tabSlugs['mail'] => [
                'smtp' => 'mail.smtp',
                'host' => 'mail.host',
                'port' => 'mail.port',
                'username' => 'mail.username',
                'password' => 'mail.password',
                'secure' => 'mail.secure',
                'from' => 'mail.from',
            ],
            $this->tabSlugs['localization'] => [
                'locale' => 'lang.locale',
                'available' => 'lang.available',
            ],
        ];
    }

    /**
     * Инициализирует базы данных из конфигурации.
     */
    public function initDatabases(): Collection
    {
        $databases = config('database.databases', []);
        $connections = config('database.connections', []);

        $mergedData = collect();

        foreach ($databases as $dbName => $dbDetails) {
            $connectionName = $dbDetails['connection'] ?? null;

            if ($connectionName && isset($connections[$connectionName])) {
                $connectionConfig = (array) $connections[$connectionName];
                $tcpConnection = $connectionConfig['connection'] ?? null;

                if ($dbName === 'default') {
                    continue;
                }

                $driver = $connectionConfig['driver'] ?? 'unknown';
                $host = $tcpConnection->host;
                $port = $tcpConnection->port;

                $driverExplode = explode('\\', $driver);
                $driver = str_replace('Driver', '', end($driverExplode));

                $mergedData->push(new Repository([
                    'databaseName' => $dbName,
                    'prefix' => $dbDetails['prefix'] ?? '',
                    'host' => "{$host}:{$port}",
                    'user' => $tcpConnection->user,
                    'database' => $tcpConnection->database,
                    'driver' => $driver,
                ]));
            }
        }

        return $mergedData;
    }

    /**
     * Возвращает настройки вкладок.
     */
    public function getTabSettings(): array
    {
        return [
            $this->tabSlugs['main_settings'] => [
                'name',
                'url',
                'timezone',
                'steam_api',
                'steam_cache_duration',
                'footer_description',
                'footer_additional',
                'create_backup',
                'maintenance_mode',
                'maintenance_message',
                'is_performance',
                'cron_mode',
                'auto_update',
                'csrf_enabled',
                'convert_to_webp',
                'development_mode',
                'debug',
                'debug_ips',
                'currency_view',
                'oferta_view',
                'oferta_url',
                'only_modal',
                'flute_key',
                'keywords',
                'description',
                'robots',
                'change_theme',
                'default_theme',
            ],
            $this->tabSlugs['additional_settings'] => [
                'share',
                'flute_copyright',
                'discord_link_roles',
                'minify',
                'logo',
                'bg_image',
                'bg_image_light',
            ],
            $this->tabSlugs['users'] => [
                'reset_password',
                'only_social',
                'only_modal',
                'confirm_email',
                'remember_me',
                'remember_me_duration',
                'check_ip',
                'security_token',
                'change_uri',
                'default_avatar',
                'default_banner',
                'captcha_enabled_login',
                'captcha_enabled_register',
                'captcha_enabled_password_reset',
                'captcha_type',
                'recaptcha_site_key',
                'recaptcha_secret_key',
                'hcaptcha_site_key',
                'hcaptcha_secret_key',
                'default_role',
            ],
            $this->tabSlugs['mail'] => [
                'smtp',
                'host',
                'port',
                'username',
                'password',
                'secure',
                'from',
            ],
            $this->tabSlugs['localization'] => [
                'locale',
                'available',
            ],
        ];
    }

    /**
     * Возвращает правила валидации для каждой вкладки.
     */
    public function getValidationRules(): array
    {
        return [
            $this->tabSlugs['main_settings'] => [
                'name' => 'required|string',
                'url' => 'required|url',
                'timezone' => 'required|timezone',
                'steam_api' => 'nullable|string',
                'steam_cache_duration' => 'nullable|integer',
                'footer_description' => 'nullable|string',
                'footer_additional' => 'nullable|string',
                'create_backup' => 'boolean',
                'maintenance_mode' => 'boolean',
                'maintenance_message' => 'nullable|string',
                'is_performance' => 'boolean',
                'cron_mode' => 'boolean',
                'auto_update' => 'boolean',
                'csrf_enabled' => 'boolean',
                'convert_to_webp' => 'boolean',
                'development_mode' => 'boolean',
                'debug' => 'boolean',
                'debug_ips' => 'nullable|array-implode',
                'currency_view' => 'required|string',
                'oferta_view' => 'boolean',
                'oferta_url' => 'nullable|string',
                'only_modal' => 'boolean',
                'flute_key' => 'nullable|string',
                'keywords' => 'nullable|string',
                'robots' => 'required|string',
                'description' => 'nullable|string',
                'change_theme' => 'boolean',
                'default_theme' => 'nullable|string',
            ],
            $this->tabSlugs['additional_settings'] => [
                'share' => 'boolean',
                'flute_copyright' => 'boolean',
                'discord_link_roles' => 'boolean',
                'minify' => 'boolean',
                'logo' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp,svg',
                'bg_image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp',
                'bg_image_light' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp',
            ],
            $this->tabSlugs['users'] => [
                'reset_password' => 'boolean',
                'only_social' => 'boolean',
                'only_modal' => 'boolean',
                'confirm_email' => 'boolean',
                'remember_me' => 'boolean',
                'remember_me_duration' => 'required|integer|min:1',
                'check_ip' => 'boolean',
                'security_token' => 'boolean',
                'change_uri' => 'boolean',
                'default_avatar' => 'nullable|string',
                'default_banner' => 'nullable|string',
                'captcha_enabled_login' => 'boolean',
                'captcha_enabled_register' => 'boolean',
                'captcha_enabled_password_reset' => 'boolean',
                'captcha_type' => 'required|string|in:recaptcha_v2,hcaptcha',
                'recaptcha_site_key' => 'nullable|string',
                'recaptcha_secret_key' => 'nullable|string',
                'hcaptcha_site_key' => 'nullable|string',
                'hcaptcha_secret_key' => 'nullable|string',
                'default_role' => 'nullable',
            ],
            $this->tabSlugs['mail'] => [
                'smtp' => 'boolean',
                'host' => 'required|string',
                'port' => 'required|integer|min:1|max:65535',
                'username' => 'required|string',
                'password' => 'nullable|string',
                'secure' => 'required|string|in:tls,ssl',
                'from' => 'required|email',
            ],
            $this->tabSlugs['localization'] => [
                'locale' => 'required|string',
                'available' => 'required|array',
            ],
        ];
    }

    /**
     * Обрабатывает булевые входные данные.
     */
    public function processBooleanInputs(array $rules, array $inputs): array
    {
        foreach ($rules as $key => $ruleSet) {
            $ruleArray = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            if (in_array('boolean', $ruleArray, true)) {
                $inputs[$key] = filter_var($inputs[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $inputs;
    }

    /**
     * Обновляет конфигурацию на основе входных данных и текущей вкладки.
     */
    public function updateConfig(array $inputs, string $currentTab): void
    {
        if ($currentTab === $this->tabSlugs['localization']) {
            config()->set('lang.locale', $inputs['locale']);

            $availableNormal = [];

            foreach ($inputs['available'] as $lang => $val) {
                if (filter_var($val, FILTER_VALIDATE_BOOLEAN) === true) {
                    $availableNormal[] = $lang;
                }
            }

            config()->set('lang.available', $availableNormal);

            config()->save();

            return;
        }

        $configUpdates = [];
        $mapping = $this->configMappings[$currentTab] ?? [];

        foreach ($inputs as $key => $value) {
            if (isset($mapping[$key])) {
                $configKey = $mapping[$key]; // e.g., 'app.name'
                [$configFile, $configPath] = explode('.', $configKey, 2);
                if (!isset($configUpdates[$configFile])) {
                    $configUpdates[$configFile] = [];
                }
                $configUpdates[$configFile][$configPath] = $value;
            }
        }

        foreach ($configUpdates as $configFile => $configData) {
            $existingConfig = config($configFile, []);

            foreach ($configData as $key => $value) {
                if (is_array($value) && empty($value)) {
                    Arr::set($existingConfig, $key, []);
                } else {
                    Arr::set($existingConfig, $key, $value);
                }
            }

            config()->set($configFile, $existingConfig);
        }

        config()->save();
    }

    /**
     * Обрабатывает входные данные как массив из строки, разделенной запятыми.
     */
    public function processArrayInputs(array $rules, array $inputs): array
    {
        foreach ($rules as $key => $ruleSet) {
            $ruleArray = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            if (in_array('array-implode', $ruleArray, true) && isset($inputs[$key])) {
                $inputs[$key] = empty($inputs[$key]) ? [] : array_map('trim', explode(',', $inputs[$key]));
            }
        }

        return $inputs;
    }

    /**
     * Обрабатывает булевые и массивные входные данные.
     */
    public function processInputs(array $rules, array $inputs): array
    {
        $inputs = $this->processBooleanInputs($rules, $inputs);
        $inputs = $this->processArrayInputs($rules, $inputs);

        return $inputs;
    }

    /**
     * Сохраняет настройки на основе текущей вкладки и входных данных.
     *
     * @throws Exception
     */
    public function saveSettings(string $currentTab, array $data): bool
    {
        $tabSettings = $this->getTabSettings();

        if (!array_key_exists($currentTab, $tabSettings)) {
            throw new Exception(__('admin-main-settings.messages.unknown_tab'));
        }

        $filteredData = collect($data)->only($tabSettings[$currentTab])->toArray();
        $rules = $this->getValidationRules()[$currentTab] ?? [];

        if (!validator()->validate($filteredData, $rules)) {
            return false;
        }

        $inputs = $this->processInputs($rules, $filteredData);

        $this->updateConfig($inputs, $currentTab);

        return true;
    }

    public function testDatabaseConnection(string $driver, string $host, int $port, string $database, string $user, ?string $password)
    {
        try {
            switch ($driver) {
                case 'mysql':
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ];

                    break;

                case 'postgres':
                    $dsn = "pgsql:host={$host};port={$port};dbname={$database};";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ];

                    break;

                default:
                    return __('admin-main-settings.messages.unsupported_driver');
            }

            $pdo = new PDO($dsn, $user, $password, $options);
            $pdo = null;

            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}
