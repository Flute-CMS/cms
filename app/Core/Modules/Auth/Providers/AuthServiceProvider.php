<?php

namespace Flute\Core\Modules\Auth\Providers;

use Flute\Core\Modules\Auth\Components\LoginComponent;
use Flute\Core\Modules\Auth\Components\PasswordResetComponent;
use Flute\Core\Modules\Auth\Components\PasswordResetTokenComponent;
use Flute\Core\Modules\Auth\Components\RegisterComponent;
use Flute\Core\Modules\Auth\Services\AuthenticationService;
use Flute\Core\Modules\Auth\Services\AuthService;
use Flute\Core\Modules\Auth\Services\SocialService;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Template\Events\TemplateInitialized;
use Flute\Core\Listeners\DefaultRoleListener;

class AuthServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            AuthenticationService::class => \DI\autowire(),
            AuthService::class => \DI\autowire(),
            SocialService::class => \DI\create(),
            'auth' => \DI\get(AuthService::class),
            'social' => \DI\get(SocialService::class),
        ]);
    }

    /**
     * Register auth service.
     */
    public function boot(\DI\Container $container) : void
    {
        if (is_installed()) {
            $this->loadRoutesFrom(cms_path('Auth/Routes/auth.php'));

            events()->addListener(TemplateInitialized::NAME, function (TemplateInitialized $event) {
                $template = $event->getTemplate();

                if (is_admin_path()) {
                    return;
                }

                if (user()->isLoggedIn()) {
                    return;
                }

                $template->registerComponent('login', LoginComponent::class);
                $template->registerComponent('register', RegisterComponent::class);
                $template->registerComponent('reset', PasswordResetComponent::class);
                $template->registerComponent('reset-token', PasswordResetTokenComponent::class);
            });

            events()->addListener(\Flute\Core\Modules\Auth\Events\UserRegisteredEvent::NAME, [DefaultRoleListener::class, 'handle']);
        }
    }
}