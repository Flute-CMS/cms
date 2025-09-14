<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

if (!defined('FLUTE_START')) {
    define('FLUTE_START', microtime(true));
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
}

if (!extension_loaded('ionCube Loader') && isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false) {
    http_response_code(500);
    exit('Flute requires ioncube_loader extension to be enabled.');
}

if (version_compare(PHP_VERSION, '8.2', '<')) {
    http_response_code(500);
    exit('Flute requires PHP version 8.2 or higher.');
}

if (!file_exists(BASE_PATH . 'vendor/autoload.php')) {
    http_response_code(500);
    exit('Folder "vendor" wasn\'t found. Please, check your files again');
}

define('FLUTE_BOOTSTRAP_START', microtime(true));

/**
 * Include the composer autoloader
 */
$loader = require BASE_PATH . 'vendor/autoload.php';

use Flute\Core\App;
use Flute\Core\Profiling\GlobalProfiler;
use Flute\Core\Modules\Admin\Providers\AdminServiceProvider;
use Flute\Core\Modules\Auth\Providers\AuthServiceProvider;
use Flute\Core\Modules\Home\Providers\HomeServiceProvider;
use Flute\Core\Modules\Icons\Providers\IconServiceProvider;
use Flute\Core\Modules\Notifications\Providers\NotificationServiceProvider;
use Flute\Core\Modules\Profile\Providers\ProfileServiceProvider;
use Flute\Core\Modules\Search\Providers\SearchServiceProvider;
use Flute\Core\Modules\Tips\Providers\TipsServiceProvider;
use Flute\Core\Modules\Translation\Providers\TranslationServiceProvider;
use Flute\Core\ServiceProviders\BreadcrumbServiceProvider;
use Flute\Core\ServiceProviders\CacheServiceProvider;
use Flute\Core\ServiceProviders\ConfigurationServiceProvider;
use Flute\Core\ServiceProviders\CookieServiceProvider;
use Flute\Core\ServiceProviders\DatabaseServiceProvider;
use Flute\Core\ServiceProviders\EmailServiceProvider;
use Flute\Core\ServiceProviders\EncryptServiceProvider;
use Flute\Core\ServiceProviders\EventsServiceProvider;
use Flute\Core\ServiceProviders\FileSystemServiceProvider;
use Flute\Core\ServiceProviders\FlashServiceProvider;
use Flute\Core\ServiceProviders\FooterServiceProvider;
use Flute\Core\ServiceProviders\LoggerServiceProvider;
use Flute\Core\ServiceProviders\ModulesServiceProvider;
use Flute\Core\ServiceProviders\NavbarServiceProvider;
use Flute\Core\ServiceProviders\RequestServiceProvider;
use Flute\Core\ServiceProviders\RouterServiceProvider;
use Flute\Core\ServiceProviders\SessionServiceProvider;
use Flute\Core\ServiceProviders\SystemHealthServiceProvider;
use Flute\Core\ServiceProviders\ThrottlerServiceProvider;
use Flute\Core\ServiceProviders\TracyBarServiceProvider;
use Flute\Core\ServiceProviders\UserServiceProvider;
use Flute\Core\ServiceProviders\ViewServiceProvider;
use Flute\Core\Modules\Installer\Providers\InstallerServiceProvider;
use Flute\Core\Modules\Page\Providers\PageServiceProvider;
use Flute\Core\Modules\Payments\Providers\PaymentServiceProvider;
use Flute\Core\ServiceProviders\CronServiceProvider;
use Flute\Core\ServiceProviders\LoggingServiceProvider;
use Flute\Core\ServiceProviders\SteamServiceProvider;
use Flute\Core\ServiceProviders\UpdateServiceProvider;
use Flute\Core\Router\Providers\AttributeRouteServiceProvider;

/**
 * Creates a new application
 */
$app = new App($loader);

App::setInstance($app);

/**
 * Sets a base path of the application
 */
$app->setBasePath(BASE_PATH);

define('FLUTE_CONTAINER_START', microtime(true));

/**
 * Start global profiling
 */
GlobalProfiler::start();

/**
 * Initializes the service providers
 */
$app->serviceProvider(FileSystemServiceProvider::class)
    ->serviceProvider(RequestServiceProvider::class)
    ->serviceProvider(ConfigurationServiceProvider::class)
    ->serviceProvider(TranslationServiceProvider::class)
    ->serviceProvider(EventsServiceProvider::class)
    ->serviceProvider(LoggerServiceProvider::class)
    ->serviceProvider(CacheServiceProvider::class)
    ->serviceProvider(SessionServiceProvider::class)
    ->serviceProvider(CronServiceProvider::class)
    ->serviceProvider(TracyBarServiceProvider::class)
    ->serviceProvider(DatabaseServiceProvider::class)
    ->serviceProvider(SystemHealthServiceProvider::class)
    ->serviceProvider(CookieServiceProvider::class)
    ->serviceProvider(EncryptServiceProvider::class)
    ->serviceProvider(BreadcrumbServiceProvider::class)
    ->serviceProvider(EmailServiceProvider::class)
    ->serviceProvider(FlashServiceProvider::class)
    ->serviceProvider(LoggingServiceProvider::class)
    ->serviceProvider(SentryServiceProvider::class)
    ->serviceProvider(ViewServiceProvider::class)
    ->serviceProvider(RouterServiceProvider::class)
    ->serviceProvider(AttributeRouteServiceProvider::class)
    ->serviceProvider(InstallerServiceProvider::class)
    ->serviceProvider(AuthServiceProvider::class)
    ->serviceProvider(NotificationServiceProvider::class)
    ->serviceProvider(UserServiceProvider::class)
    ->serviceProvider(NavbarServiceProvider::class)
    ->serviceProvider(FooterServiceProvider::class)
    ->serviceProvider(ThrottlerServiceProvider::class)
    ->serviceProvider(PaymentServiceProvider::class)
    ->serviceProvider(SteamServiceProvider::class)
    ->serviceProvider(UpdateServiceProvider::class)
    ->serviceProvider(ModulesServiceProvider::class)
    ->serviceProvider(ProfileServiceProvider::class)
    ->serviceProvider(PageServiceProvider::class)
    ->serviceProvider(TipsServiceProvider::class)
    ->serviceProvider(SearchServiceProvider::class)
    ->serviceProvider(HomeServiceProvider::class)
    ->serviceProvider(AdminServiceProvider::class)
    ->serviceProvider(IconServiceProvider::class);

/**
 * Build and compile our container
 */
$app->buildContainer();

define('FLUTE_CONTAINER_END', microtime(true));

/**
 * Boot all registered service providers
 */
$app->bootServiceProviders();

/**
 * Register shutdown function to stop profiling
 */
register_shutdown_function(function() {
    GlobalProfiler::stop();
});

/**
 * Returns the application
 */
return $app;
