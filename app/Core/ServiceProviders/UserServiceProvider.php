<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Events\ResponseEvent;
use Flute\Core\Events\UserChangedEvent;
use Flute\Core\Listeners\UserChangeResponseListener;
use Flute\Core\Services\UserService;
use Flute\Core\Support\AbstractServiceProvider;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * Registers services provided by the service provider.
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            UserService::class => \DI\autowire(),
            "user" => \DI\get(UserService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_installed()) {
            $container->get(UserService::class)->getCurrentUser();

            if (!is_cli()) {
                $events = $container->get('events');
                $userChangeListener = $container->get(UserChangeResponseListener::class);

                $events->addListener(UserChangedEvent::NAME, [$userChangeListener, 'onUserChanged']);
                $events->addDeferredListener(ResponseEvent::NAME, [$userChangeListener, 'onResponse']);
            }
        }
    }
}
