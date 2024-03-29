<?php


namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\UserService;
use Flute\Core\Support\AbstractServiceProvider;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * Регистрирует сервисы, предоставляемые сервис-провайдером.
     */
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            UserService::class => \DI\autowire(),
            "user" => \DI\get(UserService::class)
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        is_installed() && $container->get('user');
    }
}
