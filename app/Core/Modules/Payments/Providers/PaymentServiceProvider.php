<?php

namespace Flute\Core\Modules\Payments\Providers;

use Flute\Core\Modules\Payments\Controllers\PaymentsViewController;
use Flute\Core\Modules\Payments\Factories\PaymentDriverFactory;
use Flute\Core\Modules\Payments\Initializers\GatewayInitializer;
use Flute\Core\Modules\Payments\Listeners\TemplateListener;
use Flute\Core\Modules\Payments\Services\PaymentsCleaner;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Template\Events\TemplateInitialized;
use Throwable;

class PaymentServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            GatewayInitializer::class => \DI\autowire(),
            PaymentsCleaner::class => \DI\autowire(),
            PaymentDriverFactory::class => \DI\autowire(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (!is_installed() || is_cli()) {
            return;
        }

        $this->loadRoutesFrom(cms_path('Payments/Routes/payments.php'));
        $this->ensureResultRoutes($container);

        try {
            /** @var \Flute\Core\Database\DatabaseConnection $db */
            $db = $container->get(\Flute\Core\Database\DatabaseConnection::class);
        } catch (Throwable $e) {
            logs('modules')->warning('Payments boot: database not ready yet: ' . $e->getMessage());
        }

        events()->addDeferredListener(TemplateInitialized::NAME, [TemplateListener::class, 'handle']);
    }

    private function ensureResultRoutes(\DI\Container $container): void
    {
        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);

        if (!$router->hasRoute('/lk/success', 'GET')) {
            $router->get('/lk/success', [PaymentsViewController::class, 'paymentSuccess']);
            logs()->warning('payments.routes.recovered', ['path' => '/lk/success']);
        }
        if (!$router->hasRoute('/lk/success', 'POST')) {
            $router->post('/lk/success', [PaymentsViewController::class, 'paymentSuccess']);
            logs()->warning('payments.routes.recovered', ['path' => '/lk/success', 'method' => 'POST']);
        }

        if (!$router->hasRoute('/lk/fail', 'GET')) {
            $router->get('/lk/fail', [PaymentsViewController::class, 'paymentFail']);
            logs()->warning('payments.routes.recovered', ['path' => '/lk/fail']);
        }
        if (!$router->hasRoute('/lk/fail', 'POST')) {
            $router->post('/lk/fail', [PaymentsViewController::class, 'paymentFail']);
            logs()->warning('payments.routes.recovered', ['path' => '/lk/fail', 'method' => 'POST']);
        }

        if (!$router->hasRoute('/ik/success', 'GET')) {
            $router->get('/ik/success', [PaymentsViewController::class, 'paymentSuccess']);
        }
        if (!$router->hasRoute('/ik/success', 'POST')) {
            $router->post('/ik/success', [PaymentsViewController::class, 'paymentSuccess']);
        }

        if (!$router->hasRoute('/ik/fail', 'GET')) {
            $router->get('/ik/fail', [PaymentsViewController::class, 'paymentFail']);
        }
        if (!$router->hasRoute('/ik/fail', 'POST')) {
            $router->post('/ik/fail', [PaymentsViewController::class, 'paymentFail']);
        }
    }
}
