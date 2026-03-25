<?php

namespace Flute\Core\Modules\Installer\Controllers;

use DateTimeZone;
use Exception;
use Flute\Core\Database\DatabaseCapabilities;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Modules\Installer\Services\InstallerView;
use Flute\Core\Modules\Installer\Services\SystemRequirements;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Services\ConfigurationService;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\SystemHealth\Migrations\CheckPermissionsMigration;
use GuzzleHttp\Client;
use PDO;
use PDOException;
use Throwable;

/**
 * Controller for handling installer requests
 */
class InstallerController extends BaseController
{
    protected InstallerView $installerView;

    protected InstallerConfig $installerConfig;

    protected ConfigurationService $configService;

    protected SystemRequirements $systemRequirements;

    public function __construct(
        InstallerView $installerView,
        InstallerConfig $installerConfig,
        ConfigurationService $configService,
        SystemRequirements $systemRequirements,
    ) {
        $this->installerView = $installerView;
        $this->installerConfig = $installerConfig;
        $this->configService = $configService;
        $this->systemRequirements = $systemRequirements;
    }

    /**
     * Display the installer welcome page
     */
    #[Route('/install', name: 'installer.welcome', methods: ['GET'])]
    public function welcome(): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        $welcomeData = [
            'preferredLanguage' => $this->getPreferredLanguage(),
            'selectedLanguage' => config('lang.locale', 'en'),
            'languages' => config('lang.available'),
            'fluteKey' => config('installer.flute_key', ''),
            'keyError' => null,
            'keyValid' => false,
        ];

        return $this->installerView->render([
            'stepView' => 'installer::yoyo.welcome',
            'stepData' => $welcomeData,
        ]);
    }

    /**
     * Process the welcome form (language change or proceed)
     */
    #[Route('/install', name: 'installer.welcome.submit', methods: ['POST'])]
    public function processWelcome(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        $action = $request->input('action', '');

        // Handle language change
        if ($action === 'setLanguage') {
            $language = $request->input('language', 'en');
            $lang = config('lang');
            $lang['locale'] = $language;
            config()->set('lang', $lang);
            config()->save();
            app()->setLang($language);

            $welcomeData = [
                'preferredLanguage' => $this->getPreferredLanguage(),
                'fluteKey' => $request->input('fluteKey', config('installer.flute_key', '')),
                'keyError' => null,
                'keyValid' => false,
                'selectedLanguage' => $language,
                'languages' => config('lang.available'),
            ];

            return $this->installerView->render([
                'stepView' => 'installer::yoyo.welcome',
                'stepData' => $welcomeData,
            ]);
        }

        // Handle validate and proceed
        $fluteKey = $request->input('fluteKey', '');
        $keyError = null;
        $keyValid = false;
        $savedKeys = array_filter([config('app.flute_key', ''), config('installer.flute_key', '')]);

        if (!empty($fluteKey) && !in_array($fluteKey, $savedKeys, true)) {
            try {
                $client = new \GuzzleHttp\Client();
                $apiResponse = $client->post(config('app.flute_market_url') . '/api/auth/accesskey', [
                    'json' => ['key' => $fluteKey],
                ]);
                $body = json_decode($apiResponse->getBody(), true);
                if ($apiResponse->getStatusCode() === 200 && isset($body['valid']) && $body['valid'] === true) {
                    $app = config('app');
                    $app['flute_key'] = $fluteKey;
                    config()->set('app', $app);
                    config()->save();
                    $keyValid = true;
                } else {
                    $keyError = $body['message'] ?? __('install.welcome.key_error');
                }
            } catch (Throwable $e) {
                $keyError = __('install.welcome.key_error');
            }

            if ($keyError) {
                $welcomeData = [
                    'preferredLanguage' => $this->getPreferredLanguage(),
                    'fluteKey' => $fluteKey,
                    'keyError' => $keyError,
                    'keyValid' => false,
                    'selectedLanguage' => config('lang.locale', 'en'),
                    'languages' => config('lang.available'),
                ];

                return $this->installerView->render([
                    'stepView' => 'installer::yoyo.welcome',
                    'stepData' => $welcomeData,
                ]);
            }
        } elseif (!empty($fluteKey) && in_array($fluteKey, $savedKeys, true)) {
            $keyValid = true;
        }

        return response()->redirect(route('installer.step', ['id' => 1]));
    }

    /**
     * Display installer step (GET)
     */
    #[Route('/install/{id}', name: 'installer.step', methods: ['GET'], where: ['id' => '\d+'])]
    public function step(FluteRequest $request, int $id): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($id < 1 || $id > $this->installerConfig->getTotalSteps()) {
            return response()->error(404, 'Installer step not found');
        }

        return $this->renderStep($id);
    }

    /**
     * Process step 2 — change driver (re-render step with new driver defaults)
     */
    #[Route('/install/2/driver', name: 'installer.step2.driver', methods: ['POST'])]
    public function changeDriver(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        $driver = $request->input('driver', 'mysql');
        $defaultPorts = ['mysql' => '3306', 'pgsql' => '5432', 'sqlite' => ''];

        return $this->renderStep(2, [
            'driver' => $driver,
            'host' => $request->input('host', 'localhost'),
            'port' => $defaultPorts[$driver] ?? '3306',
            'database' => $request->input('database', ''),
            'username' => $request->input('username', ''),
            'password' => $request->input('password', ''),
            'prefix' => $request->input('prefix', 'flute_'),
            'isConnected' => false,
            'errorMessage' => null,
        ]);
    }

    /**
     * Process step 2 — test database connection (HTMX partial)
     */
    #[Route('/install/2/test', name: 'installer.step2.test', methods: ['POST'])]
    public function testDatabaseConnection(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        $driver = $request->input('driver', 'mysql');
        $host = $request->input('host', 'localhost');
        $port = $request->input('port', '3306');
        $database = $request->input('database', '');
        $username = $request->input('username', '');
        $password = $request->input('password', '');
        $prefix = $request->input('prefix', 'flute_');

        $errorMessage = null;
        $isConnected = false;
        $fieldErrors = [];

        try {
            if (empty($host) && $driver !== 'sqlite') {
                $fieldErrors['host'] = __('install.database.error_host_required');

                return $this->renderDatabaseStep($request, $isConnected, null, $fieldErrors);
            }

            if (empty($database)) {
                $fieldErrors['database'] = __('install.database.error_database_required');

                return $this->renderDatabaseStep($request, $isConnected, null, $fieldErrors);
            }

            if ($driver !== 'sqlite' && !preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
                $fieldErrors['database'] = __('install.database.error_invalid_name');

                return $this->renderDatabaseStep($request, $isConnected, null, $fieldErrors);
            }

            if ($driver !== 'sqlite' && !preg_match('/^[a-zA-Z0-9._\-]+$/', $host)) {
                $fieldErrors['host'] = __('install.database.error_host_required');

                return $this->renderDatabaseStep($request, $isConnected, null, $fieldErrors);
            }

            if (!empty($port) && !ctype_digit((string) $port)) {
                $fieldErrors['port'] = __('install.database.error_host_required');

                return $this->renderDatabaseStep($request, $isConnected, null, $fieldErrors);
            }

            // For SQLite
            if ($driver === 'sqlite') {
                $databaseDir = dirname(path('storage/database/' . $database));
                if (!is_dir($databaseDir) && !mkdir($databaseDir, 0o755, true)) {
                    $errorMessage = __('install.database.error_sqlite_dir');

                    return $this->renderDatabaseStep($request, $isConnected, $errorMessage);
                }
                $isConnected = true;

                $this->installerConfig->setParam('database', [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'prefix' => $prefix,
                ]);

                try {
                    $this->saveDatabaseConfig($driver, $host, $port, $database, $username, $password, $prefix);
                } catch (Throwable $e) {
                    $errorMessage = $e->getMessage();
                    $isConnected = false;
                }

                return $this->renderDatabaseStep($request, $isConnected, $errorMessage);
            }

            // Set up connection based on driver
            switch ($driver) {
                case 'mysql':
                    $dsn = "mysql:host={$host};port={$port};";

                    break;
                case 'pgsql':
                    $dsn = "pgsql:host={$host};port={$port};";

                    break;
                default:
                    $errorMessage = __('install.database.error_driver_not_supported');

                    return $this->renderDatabaseStep($request, $isConnected, $errorMessage);
            }

            // Test connection to server without database first
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Check database server version and capabilities
            $dbWarnings = [];
            if ($driver !== 'sqlite') {
                $capabilities = DatabaseCapabilities::fromPdo($pdo, $driver);

                if (!$capabilities->meetsMinimumVersion()) {
                    $errorMessage = __('install.database.error_version_too_old', [
                        'server' => $capabilities->getServerLabel(),
                        'current' => $capabilities->getCleanVersion(),
                        'required' => $capabilities->getMinimumVersion(),
                    ]);

                    return $this->renderDatabaseStep($request, false, $errorMessage);
                }

                $dbWarnings = $capabilities->getWarnings();
            }

            // Try to select the database
            try {
                $pdo = new PDO($dsn . "dbname={$database}", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                // If database doesn't exist, create it
                if ($driver === 'mysql') {
                    $pdo->exec(
                        "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    );
                } elseif ($driver === 'pgsql') {
                    $pdo->exec("CREATE DATABASE \"{$database}\"");
                }
            }

            // Save configuration
            $this->installerConfig->setParam('database', [
                'driver' => $driver,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'prefix' => $prefix,
            ]);

            try {
                $this->saveDatabaseConfig($driver, $host, $port, $database, $username, $password, $prefix);
                $isConnected = true;
            } catch (Throwable $e) {
                $errorMessage = __('install.database.error_migration') . ': ' . $e->getMessage();
                $isConnected = false;
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $isConnected = false;
        }

        return $this->renderDatabaseStep($request, $isConnected, $errorMessage, $fieldErrors, $dbWarnings ?? []);
    }

    /**
     * Process step 2 — save database config and advance
     */
    #[Route('/install/2', name: 'installer.step2.save', methods: ['POST'])]
    public function saveDatabaseStep(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        // Check if database is already connected from the test
        $dbConfig = $this->installerConfig->getParams('database');
        if ($dbConfig) {
            $this->installerConfig->setCurrentStep(3);
            config()->save();

            return response()->redirect(route('installer.step', ['id' => 3]));
        }

        // If not tested yet, redirect back
        return response()->redirect(route('installer.step', ['id' => 2]));
    }

    /**
     * Process step 3 — save account and site settings
     */
    #[Route('/install/3', name: 'installer.step3.save', methods: ['POST'])]
    public function saveAccountAndSite(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        $errorMessage = null;

        try {
            $name = $request->input('name', '');
            $email = $request->input('email', '');
            $login = $request->input('login', '');
            $password = $request->input('password', '');
            $passwordConfirmation = $request->input('password_confirmation', '');
            $siteName = $request->input('siteName', 'Flute');
            $siteDescription = $request->input('siteDescription', '');
            $siteUrl = $request->input('siteUrl', '');
            $timezone = $request->input('timezone', 'UTC');
            $siteKeywords = $request->input('siteKeywords', 'Flute, game servers, gaming');

            // Validate admin fields
            $adminData = [
                'name' => $name,
                'email' => $email,
                'login' => $login,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ];

            $adminRules = [
                'name' => 'required|human-name|min-str-len:3|max-str-len:255',
                'email' => 'required|email|max-str-len:255',
                'login' => 'required|regex:/^[a-zA-Z0-9._-]+$/|min-str-len:6|max-str-len:20',
                'password' => 'required|min-str-len:8|confirmed',
                'password_confirmation' => 'required',
            ];

            $validated = $this->validate($adminData, $adminRules);

            if ($validated !== true) {
                // If admin was already created in a previous attempt, allow proceeding
                if ($this->installerConfig->getParams('admin_user_exists')) {
                    return response()->redirect(route('installer.step', ['id' => 4]));
                }

                return $this->renderStep(3, [
                    'errorMessage' => is_object($validated) && method_exists($validated, 'getErrors')
                        ? implode(', ', $validated->getErrors()->all())
                        : __('install.account_site.error_validation'),
                    'name' => $name,
                    'email' => $email,
                    'login' => $login,
                    'siteName' => $siteName,
                    'siteDescription' => $siteDescription,
                    'siteUrl' => $siteUrl,
                    'timezone' => $timezone,
                    'siteKeywords' => $siteKeywords,
                ]);
            }

            // Validate site fields
            $siteData = [
                'siteName' => $siteName,
                'siteUrl' => $siteUrl,
                'timezone' => $timezone,
            ];

            $siteRules = [
                'siteName' => 'required|max-str-len:100',
                'siteUrl' => 'required|url',
                'timezone' => 'required',
            ];

            $validatedSite = $this->validate($siteData, $siteRules);

            if ($validatedSite !== true) {
                return $this->renderStep(3, [
                    'errorMessage' => is_object($validatedSite) && method_exists($validatedSite, 'getErrors')
                        ? implode(', ', $validatedSite->getErrors()->all())
                        : __('install.account_site.error_validation'),
                    'name' => $name,
                    'email' => $email,
                    'login' => $login,
                    'siteName' => $siteName,
                    'siteDescription' => $siteDescription,
                    'siteUrl' => $siteUrl,
                    'timezone' => $timezone,
                    'siteKeywords' => $siteKeywords,
                ]);
            }

            // Create or update admin user
            $existingUser = null;

            try {
                $existingUser = User::findOne(['login' => $login]);
            } catch (Throwable $e) {
                // Table might not exist yet
            }

            if ($existingUser) {
                $existingUser->name = $name;
                $existingUser->email = $email;
                $existingUser->setPassword($password);
                $existingUser->save();
            } else {
                $user = new User();
                $user->name = $name;
                $user->email = $email;
                $user->login = $login;
                $user->avatar = config('profile.default_avatar');
                $user->banner = config('profile.default_banner');
                $user->setPassword($password);
                $user->verified = true;

                $adminRole = Role::findOne(['name' => 'admin']);

                if ($adminRole) {
                    $user->addRole($adminRole);
                }

                $user->save();
            }

            $this->installerConfig->setParams([
                'admin_user_exists' => true,
                'admin_name' => $name,
                'admin_email' => $email,
                'admin_login' => $login,
            ]);

            // Save site configuration
            $appConfig = config('app');
            $appConfig['name'] = $siteName;
            $appConfig['description'] = $siteDescription;
            $appConfig['keywords'] = $siteKeywords;
            $appConfig['url'] = $siteUrl;
            $appConfig['timezone'] = $timezone;

            config()->set('app', $appConfig);
            config()->save();

            $this->installerConfig->setCurrentStep(4);
            config()->save();

            return response()->redirect(route('installer.step', ['id' => 4]));
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->renderStep(3, [
            'errorMessage' => $errorMessage,
            'name' => $request->input('name', ''),
            'email' => $request->input('email', ''),
            'login' => $request->input('login', ''),
            'siteName' => $request->input('siteName', 'Flute'),
            'siteDescription' => $request->input('siteDescription', ''),
            'siteUrl' => $request->input('siteUrl', ''),
            'timezone' => $request->input('timezone', 'UTC'),
            'siteKeywords' => $request->input('siteKeywords', ''),
        ]);
    }

    /**
     * Process step 4 — save customize settings (languages + modules)
     */
    #[Route('/install/4', name: 'installer.step4.save', methods: ['POST'])]
    public function saveLanguages(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        try {
            $languages = $request->input('languages', []);
            $languages = array_values(array_unique(array_filter($languages)));

            $currentLocale = config('lang.locale', 'en');
            if (!in_array($currentLocale, $languages, true)) {
                $languages[] = $currentLocale;
            }

            $langConfig = config('lang');
            $langConfig['available'] = $languages;
            config()->set('lang', $langConfig);
            config()->save();

            $this->installerConfig->setCurrentStep(5);
            config()->save();

            return $this->renderStepWithPush(5);
        } catch (Exception $e) {
            return $this->renderStep(4, [
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process step 5 — save selected modules
     */
    #[Route('/install/5', name: 'installer.step5.save', methods: ['POST'])]
    public function saveModules(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        try {
            $modules = $request->input('modules', []);
            $modules = array_values(array_filter($modules));
            $this->installerConfig->setParam('selected_modules', $modules);

            $this->installerConfig->setCurrentStep(6);
            config()->save();

            return $this->renderStepWithPush(6);
        } catch (Exception $e) {
            return $this->renderStep(5, [
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process step 6 — save launch settings and finish
     */
    #[Route('/install/6', name: 'installer.step6.save', methods: ['POST'])]
    public function saveAndLaunch(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        $errorMessage = null;

        try {
            $appConfig = config('app');
            $appConfig['cron_mode'] = $request->input('cron_mode') === '1';
            $appConfig['maintenance_mode'] = $request->input('maintenance_mode') === '1';
            $appConfig['tips'] = $request->input('tips') === '1';
            $appConfig['share'] = $request->input('share') === '1';
            $appConfig['flute_copyright'] = $request->input('flute_copyright') === '1';
            $appConfig['convert_to_webp'] = $request->input('convert_to_webp') === '1';
            $appConfig['csrf_enabled'] = $request->input('csrf_enabled') === '1';
            $appConfig['change_theme'] = $request->input('change_theme') === '1';
            $appConfig['is_performance'] = $request->input('is_performance') === '1';
            $appConfig['robots'] = $request->input('robots', 'index, follow');
            $appConfig['steam_api'] = $request->input('steam_api', '');
            $appConfig['default_theme'] = $request->input('default_theme', 'dark');

            config()->set('app', $appConfig);
            config()->save();

            $user = User::query()
                ->load('roles.permissions')
                ->where('verified', true)
                ->where(['roles.permissions.name' => 'admin.boss'])
                ->fetchOne();

            if (!$user) {
                return $this->renderStep(6, [
                    'errorMessage' => __('install.launch.error_no_admin'),
                ]);
            }

            auth()->authenticateById($user->id);

            $this->installerConfig->markAsInstalled();

            return $this->installerView->render([
                'stepView' => 'installer::yoyo.finish',
                'stepData' => [],
            ], 6);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->renderStep(6, [
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Render a step view with default data for the step
     */
    protected function renderStep(int $id, array $extraData = []): mixed
    {
        $currentStep = $this->installerConfig->getCurrentStep();

        // Only allow advancing one step at a time
        if ($id > ( $currentStep + 1 )) {
            $id = $currentStep > 0 ? $currentStep : 1;
        }

        if ($id > $currentStep && $id === ( $currentStep + 1 )) {
            $this->installerConfig->setCurrentStep($id);
            config()->save();
        }

        $stepData = $this->getStepData($id);
        $data = array_merge($stepData, $extraData);

        return $this->installerView->renderStep($id, $data);
    }

    /**
     * Render next step and push the URL via HX-Push-Url header
     */
    protected function renderStepWithPush(int $id, array $extraData = []): mixed
    {
        $stepData = $this->getStepData($id);
        $data = array_merge($stepData, $extraData);
        $html = $this->installerView->renderStep($id, $data);
        $url = route('installer.step', ['id' => $id]);

        return response()->make($html, 200, [
            'HX-Push-Url' => $url,
        ]);
    }

    /**
     * Get default data for each step
     */
    protected function getStepData(int $step): array
    {
        return match ($step) {
            1 => $this->getSystemCheckData(),
            2 => $this->getDatabaseData(),
            3 => $this->getAccountSiteData(),
            4 => $this->getLanguagesData(),
            5 => $this->getModulesData(),
            6 => $this->getLaunchData(),
            default => [],
        };
    }

    /**
     * Step 1: System check data
     */
    protected function getSystemCheckData(): array
    {
        return [
            'phpRequirements' => $this->systemRequirements->checkPhpRequirements(),
            'extensionRequirements' => $this->systemRequirements->checkExtensionRequirements(),
            'directoryRequirements' => $this->systemRequirements->checkDirectoryRequirements(),
            'ionCubeCheck' => $this->systemRequirements->checkIonCubeLoader(),
            'allRequirementsMet' => $this->systemRequirements->allRequirementsMet(),
        ];
    }

    /**
     * Step 2: Database data
     */
    protected function getDatabaseData(): array
    {
        $drivers = [
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
        ];

        $driver = 'mysql';
        $host = 'localhost';
        $port = '3306';
        $database = '';
        $username = '';
        $password = '';
        $prefix = 'flute_';
        $isConnected = false;
        $errorMessage = null;

        try {
            $connection = config('database.connections.default.connection');

            if ($connection && isset($connection->database) && $connection->database) {
                $isConnected = true;

                $dbConfig = $this->installerConfig->getParams('database');
                if ($dbConfig) {
                    $driver = $dbConfig['driver'] ?? $driver;
                    $host = $dbConfig['host'] ?? $host;
                    $port = $dbConfig['port'] ?? $port;
                    $database = $dbConfig['database'] ?? $database;
                    $username = $dbConfig['username'] ?? $username;
                    $password = $dbConfig['password'] ?? $password;
                    $prefix = $dbConfig['prefix'] ?? $prefix;
                }
            }
        } catch (Throwable $e) {
            // Not yet configured
        }

        return [
            'drivers' => $drivers,
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'prefix' => $prefix,
            'errorMessage' => $errorMessage,
            'isConnected' => $isConnected,
            'fieldErrors' => [],
        ];
    }

    /**
     * Step 3: Account and site data
     */
    protected function getAccountSiteData(): array
    {
        $appConfig = config('app');

        $siteUrl = $appConfig['url'] ?? '';
        if (empty($siteUrl)) {
            $protocol =
                !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ( $_SERVER['SERVER_PORT'] ?? 80 ) == 443
                    ? 'https://'
                    : 'http://';
            $siteUrl = $protocol . ( $_SERVER['HTTP_HOST'] ?? 'localhost' );
        }

        return [
            'name' => $this->installerConfig->getParams('admin_name', ''),
            'email' => $this->installerConfig->getParams('admin_email', ''),
            'login' => $this->installerConfig->getParams('admin_login', ''),
            'siteName' => $appConfig['name'] ?? 'Flute',
            'siteDescription' => $appConfig['description'] ?? '',
            'siteUrl' => $siteUrl,
            'timezone' => $appConfig['timezone'] ?? 'UTC',
            'siteKeywords' => $appConfig['keywords'] ?? 'Flute, game servers, gaming',
            'timezones' => $this->getTimezones(),
            'errorMessage' => null,
        ];
    }

    /**
     * Step 4: Languages data
     */
    protected function getLanguagesData(): array
    {
        $flagOverrides = [
            'cs' => 'cz',
            'da' => 'dk',
            'el' => 'gr',
            'he' => 'il',
            'hi' => 'in',
            'ko' => 'kr',
            'ta' => 'in',
            'bn' => 'bd',
            'ms' => 'my',
        ];

        $locale = config('lang.locale', 'en');
        $langsFile = path("i18n/{$locale}/langs.php");

        if (!file_exists($langsFile)) {
            $langsFile = path('i18n/en/langs.php');
        }

        $langsMap = file_exists($langsFile) ? ( require $langsFile ) : [];

        $allLangs = array_keys($langsMap);
        $enabledLangs = config('lang.available', []);
        $currentLocale = config('lang.locale', 'en');

        $allLanguages = [];
        foreach ($allLangs as $code) {
            $langKey = "langs.{$code}";
            $translated = __($langKey);
            $name = $translated !== $langKey ? $translated : $langsMap[$code] ?? strtoupper($code);
            $flagCode = $flagOverrides[$code] ?? $code;

            $allLanguages[] = [
                'code' => $code,
                'native' => $name,
                'flag' => $flagCode,
            ];
        }

        usort($allLanguages, static function ($a, $b) use ($currentLocale) {
            if ($a['code'] === $currentLocale) {
                return -1;
            }
            if ($b['code'] === $currentLocale) {
                return 1;
            }

            return 0;
        });

        return [
            'allLanguages' => $allLanguages,
            'enabledLanguages' => $enabledLangs,
            'errorMessage' => null,
        ];
    }

    /**
     * Step 5: Modules data (marketplace)
     */
    protected function getModulesData(): array
    {
        $modules = [];
        $recommended = [];
        $modulesError = null;
        $noKey = false;
        $fluteKey = config('app.flute_key', '');
        $recommendedSlugs = ['Monitoring', 'SteamFriends', 'MiniBalance', 'SteamInfo', 'SteamProfile', 'API'];

        if (empty($fluteKey)) {
            $noKey = true;
        } else {
            try {
                $client = new Client([
                    'base_uri' => rtrim(config('app.flute_market_url', 'https://flute-cms.com'), '/'),
                    'timeout' => 15,
                    'http_errors' => false,
                ]);

                $response = $client->get('/api/external/modules', [
                    'query' => [
                        'accessKey' => $fluteKey,
                        'php' => substr(PHP_VERSION, 0, 3),
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    $body = json_decode($response->getBody()->getContents(), true);
                    $allModules = is_array($body) ? $body : [];

                    // Filter out paid modules
                    $allModules = array_filter($allModules, static fn($m) => empty($m['isPaid']));

                    // Split into recommended and others
                    foreach ($allModules as $module) {
                        $slug = $module['name'] ?? $module['slug'] ?? '';
                        if (in_array($slug, $recommendedSlugs, true)) {
                            $recommended[] = $module;
                        } else {
                            $modules[] = $module;
                        }
                    }

                    // Sort recommended by the defined order
                    usort($recommended, static function ($a, $b) use ($recommendedSlugs) {
                        $aIdx = array_search($a['name'] ?? $a['slug'] ?? '', $recommendedSlugs);
                        $bIdx = array_search($b['name'] ?? $b['slug'] ?? '', $recommendedSlugs);

                        return $aIdx - $bIdx;
                    });

                    // Sort others alphabetically
                    usort($modules, static fn($a, $b) => strcasecmp(
                        $a['name'] ?? $a['slug'] ?? '',
                        $b['name'] ?? $b['slug'] ?? '',
                    ));
                } else {
                    $modulesError = __('install.modules.fetch_error');
                }
            } catch (Throwable $e) {
                $modulesError = __('install.modules.fetch_error');
            }
        }

        return [
            'recommended' => $recommended,
            'modules' => $modules,
            'modulesError' => $modulesError,
            'noKey' => $noKey,
            'errorMessage' => null,
        ];
    }

    /**
     * Step 6: Launch data
     */
    protected function getLaunchData(): array
    {
        $appConfig = config('app');

        return [
            'cron_mode' => filter_var($appConfig['cron_mode'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'maintenance_mode' => filter_var($appConfig['maintenance_mode'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'tips' => filter_var($appConfig['tips'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'share' => filter_var($appConfig['share'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'flute_copyright' => filter_var($appConfig['flute_copyright'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'convert_to_webp' => filter_var($appConfig['convert_to_webp'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'csrf_enabled' => filter_var($appConfig['csrf_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'change_theme' => filter_var($appConfig['change_theme'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_performance' => filter_var($appConfig['is_performance'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'robots' => $appConfig['robots'] ?? 'index, follow',
            'steam_api' => $appConfig['steam_api'] ?? '',
            'default_theme' => $appConfig['default_theme'] ?? 'dark',
            'errorMessage' => null,
        ];
    }

    /**
     * Save the database configuration and run migrations
     */
    protected function saveDatabaseConfig(
        string $driver,
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        string $prefix,
    ): void {
        $config = config('database');

        if ($driver === 'mysql') {
            $config['connections']['default'] = \Cycle\Database\Config\MySQLDriverConfig::__set_state([
                'options' => [
                    'withDatetimeMicroseconds' => false,
                    'logInterpolatedQueries' => false,
                    'logQueryParameters' => false,
                ],
                'defaultOptions' => [
                    'withDatetimeMicroseconds' => false,
                    'logInterpolatedQueries' => false,
                    'logQueryParameters' => false,
                ],
                'connection' => \Cycle\Database\Config\MySQL\TcpConnectionConfig::__set_state([
                    'nonPrintableOptions' => [
                        0 => 'password',
                        1 => 'PWD',
                    ],
                    'user' => $username,
                    'password' => $password,
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_CASE => PDO::CASE_NATURAL,
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                        PDO::ATTR_PERSISTENT => true,
                        PDO::ATTR_TIMEOUT => 5,
                    ],
                    'port' => (int) $port,
                    'database' => $database,
                    'host' => $host,
                    'charset' => 'utf8mb4',
                ]),
                'driver' => 'Cycle\\Database\\Driver\\MySQL\\MySQLDriver',
                'reconnect' => true,
                'timezone' => 'UTC',
                'queryCache' => true,
                'readonlySchema' => false,
                'readonly' => false,
            ]);
        } elseif ($driver === 'pgsql') {
            $config['connections']['default'] = \Cycle\Database\Config\PostgresDriverConfig::__set_state([
                'options' => [
                    'withDatetimeMicroseconds' => false,
                    'logInterpolatedQueries' => false,
                    'logQueryParameters' => false,
                ],
                'defaultOptions' => [
                    'withDatetimeMicroseconds' => false,
                    'logInterpolatedQueries' => false,
                    'logQueryParameters' => false,
                ],
                'connection' => \Cycle\Database\Config\Postgres\TcpConnectionConfig::__set_state([
                    'nonPrintableOptions' => [
                        0 => 'password',
                        1 => 'PWD',
                    ],
                    'user' => $username,
                    'password' => $password,
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_CASE => PDO::CASE_NATURAL,
                    ],
                    'port' => (int) $port,
                    'database' => $database,
                    'host' => $host,
                    'schema' => 'public',
                ]),
                'driver' => 'Cycle\\Database\\Driver\\Postgres\\PostgresDriver',
                'reconnect' => true,
                'timezone' => 'UTC',
                'queryCache' => true,
                'readonlySchema' => false,
                'readonly' => false,
            ]);
        } elseif ($driver === 'sqlite') {
            $config['connections']['default'] = \Cycle\Database\Config\SQLiteDriverConfig::__set_state([
                'options' => [
                    'withDatetimeMicroseconds' => false,
                    'logInterpolatedQueries' => false,
                    'logQueryParameters' => false,
                ],
                'defaultOptions' => [
                    'withDatetimeMicroseconds' => false,
                    'logInterpolatedQueries' => false,
                    'logQueryParameters' => false,
                ],
                'connection' => \Cycle\Database\Config\SQLite\FileConnectionConfig::__set_state([
                    'nonPrintableOptions' => [
                        0 => 'password',
                        1 => 'PWD',
                    ],
                    'filename' => path('storage/database/' . $database),
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ],
                ]),
                'driver' => 'Cycle\\Database\\Driver\\SQLite\\SQLiteDriver',
                'reconnect' => true,
                'timezone' => 'UTC',
                'queryCache' => true,
                'readonlySchema' => false,
                'readonly' => false,
            ]);
        }

        $config['databases']['default']['prefix'] = $prefix;

        config()->set('database', $config);
        config()->save();

        app(DatabaseConnection::class)->recompileOrmSchema(false);
        $this->createNecessaryRolesAndPermissions();
    }

    /**
     * Create necessary roles and permissions after database setup
     */
    protected function createNecessaryRolesAndPermissions(): void
    {
        app(CheckPermissionsMigration::class)->run();

        if (!Role::findOne(['name' => 'admin']) && ( $permission = Permission::findOne(['name' => 'admin.boss']) )) {
            $role = new Role();
            $role->name = 'admin';
            $role->priority = 2;
            $role->addPermission($permission);
            $role->save();
        }

        if (!Role::findOne(['name' => 'user'])) {
            $role = new Role();
            $role->name = 'user';
            $role->priority = 1;
            $role->save();
        }
    }

    /**
     * Re-render the full database step with connection result
     */
    protected function renderDatabaseStep(
        FluteRequest $request,
        bool $isConnected,
        ?string $errorMessage,
        array $fieldErrors = [],
        array $dbWarnings = [],
    ): mixed {
        return $this->renderStep(2, [
            'driver' => $request->input('driver', 'mysql'),
            'host' => $request->input('host', 'localhost'),
            'port' => $request->input('port', '3306'),
            'database' => $request->input('database', ''),
            'username' => $request->input('username', ''),
            'password' => $request->input('password', ''),
            'prefix' => $request->input('prefix', 'flute_'),
            'isConnected' => $isConnected,
            'errorMessage' => $errorMessage,
            'fieldErrors' => $fieldErrors,
            'dbWarnings' => $dbWarnings,
        ]);
    }

    /**
     * Get list of timezones
     */
    protected function getTimezones(): array
    {
        $timezones = [];
        $regions = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Arctic' => DateTimeZone::ARCTIC,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
            'UTC' => DateTimeZone::UTC,
        ];

        foreach ($regions as $name => $mask) {
            $zones = DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $zone) {
                $timezones[$zone] = $zone;
            }
        }

        return $timezones;
    }

    /**
     * Get the preferred language
     */
    protected function getPreferredLanguage(): string
    {
        return translation()->getPreferredLanguage();
    }
}
