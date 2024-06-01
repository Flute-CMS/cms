<?php

declare(strict_types=1);

use Flute\Core\App;
use Flute\Core\ServiceProviders\AdminServiceProvider;
use Flute\Core\ServiceProviders\AuthServiceProvider;
use Flute\Core\ServiceProviders\EventNotificationsServiceProvider;
use Flute\Core\ServiceProviders\PaymentServiceProvider;
use Flute\Core\ServiceProviders\SystemHealthServiceProvider;
use Flute\Core\ServiceProviders\TracyBarServiceProvider;
use Flute\Core\ServiceProviders\EncryptServiceProvider;
use Flute\Core\ServiceProviders\FlashServiceProvider;
use Flute\Core\ServiceProviders\FooterServiceProvider;
use Flute\Core\ServiceProviders\FormServiceProvider;
use Flute\Core\ServiceProviders\HomeServiceProvider;
use Flute\Core\ServiceProviders\InstallerServiceProvider;
use Flute\Core\ServiceProviders\ModulesServiceProvider;
use Flute\Core\ServiceProviders\NavbarServiceProvider;
use Flute\Core\ServiceProviders\NotificationServiceProvider;
use Flute\Core\ServiceProviders\PageServiceProvider;
use Flute\Core\ServiceProviders\ProfileServiceProvider;
use Flute\Core\ServiceProviders\RequestServiceProvider;
use Flute\Core\ServiceProviders\SearchServiceProvider;
use Flute\Core\ServiceProviders\SocialServiceProvider;
use Flute\Core\ServiceProviders\ThrottlerServiceProvider;
use Flute\Core\ServiceProviders\TipsServiceProvider;
use Flute\Core\ServiceProviders\TranslationServiceProvider;
use Flute\Core\ServiceProviders\BreadcrumbServiceProvider;
use Flute\Core\ServiceProviders\CacheServiceProvider;
use Flute\Core\ServiceProviders\ConfigurationServiceProvider;
use Flute\Core\ServiceProviders\CookieServiceProvider;
use Flute\Core\ServiceProviders\DatabaseServiceProvider;
use Flute\Core\ServiceProviders\EventsServiceProvider;
use Flute\Core\ServiceProviders\FileSystemServiceProvider;
use Flute\Core\ServiceProviders\LoggerServiceProvider;
use Flute\Core\ServiceProviders\RouterServiceProvider;
use Flute\Core\ServiceProviders\SessionServiceProvider;
use Flute\Core\ServiceProviders\UserServiceProvider;
use Flute\Core\ServiceProviders\ViewServiceProvider;
use Flute\Core\ServiceProviders\WidgetServiceProvider;
use Flute\Core\ServiceProviders\EmailServiceProvider;

if (!file_exists(BASE_PATH . 'vendor/autoload.php')) {
    exit('Folder "vendor" wasn\'t found. Please, check your files again');
}

/**
 * Include the composer autoloader
 */
$loader = require BASE_PATH . 'vendor/autoload.php';

/**
 * Creates a new application
 */
$app = new App($loader);

// Set the global instance
App::setInstance($app);

/**
 * Sets a base path of the application
 */
$app->setBasePath(BASE_PATH);

/**
 * Initializes the service providers
 */
$app->serviceProvider(new FileSystemServiceProvider)
    ->serviceProvider(new ConfigurationServiceProvider)
    ->serviceProvider(new LoggerServiceProvider)
    ->serviceProvider(new RequestServiceProvider)
    ->serviceProvider(new CacheServiceProvider)
    ->serviceProvider(new EventsServiceProvider)
    ->serviceProvider(new TracyBarServiceProvider)
    ->serviceProvider(new SessionServiceProvider)
    ->serviceProvider(new DatabaseServiceProvider)
    ->serviceProvider(new SystemHealthServiceProvider)
    ->serviceProvider(new CookieServiceProvider)
    ->serviceProvider(new TranslationServiceProvider)
    ->serviceProvider(new EncryptServiceProvider)
    ->serviceProvider(new BreadcrumbServiceProvider)
    ->serviceProvider(new EmailServiceProvider)
    ->serviceProvider(new ViewServiceProvider)
    ->serviceProvider(new RouterServiceProvider)
    ->serviceProvider(new InstallerServiceProvider)
    ->serviceProvider(new FormServiceProvider)
    ->serviceProvider(new AuthServiceProvider)
    ->serviceProvider(new NotificationServiceProvider)
    ->serviceProvider(new EventNotificationsServiceProvider)
    ->serviceProvider(new UserServiceProvider)
    ->serviceProvider(new NavbarServiceProvider)
    ->serviceProvider(new FooterServiceProvider)
    ->serviceProvider(new FlashServiceProvider)
    ->serviceProvider(new ThrottlerServiceProvider)
    ->serviceProvider(new WidgetServiceProvider)
    ->serviceProvider(new PaymentServiceProvider)
    ->serviceProvider(new ModulesServiceProvider)
    ->serviceProvider(new PageServiceProvider)
    ->serviceProvider(new SocialServiceProvider)
    ->serviceProvider(new TipsServiceProvider)
    ->serviceProvider(new SearchServiceProvider)

    // Зачем я так сделал? Для чего было создано куча классов для каждой из страниц?
    // Дело в том, что каждая страница будет иметь уникальный набор виджетов, различные параметры
    // для доступа к этим страницам, будь то права или что-то еще, и поэтому наиболее логичным выходом
    // я увидел создание отдельных сервис провайдеров для каждого типа страниц (Авторизация, профиль, главная, настройки пользователя и т.д.)
    ->serviceProvider(new HomeServiceProvider)
    ->serviceProvider(new AdminServiceProvider)
    ->serviceProvider(new ProfileServiceProvider);

/**
 * Build and compile our container
 */
$app->buildContainer();

/**
 * Boot all registered service providers
 */
$app->bootServiceProviders();

/**
 * Returns the application
 */
return $app;