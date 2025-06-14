<?php

namespace Flute\Core\Modules\Payments\Providers;

use Flute\Core\Modules\Payments\Initializers\GatewayInitializer;
use Flute\Core\Modules\Payments\Services\PaymentsCleaner;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Template\Events\TemplateInitialized;
use Flute\Core\Modules\Payments\Factories\PaymentDriverFactory;
use Flute\Core\Modules\Payments\Listeners\TemplateListener;

class PaymentServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            GatewayInitializer::class => \DI\autowire(),
            PaymentsCleaner::class => \DI\autowire(),
            PaymentDriverFactory::class => \DI\autowire(),
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        if (!is_installed() || is_cli())
            return;

        $this->loadRoutesFrom(cms_path('Payments/Routes/payments.php'));

        $container->get(GatewayInitializer::class);
        $container->get(PaymentsCleaner::class)->cleanOldPayments();

        events()->addDeferredListener(TemplateInitialized::NAME, [TemplateListener::class, 'handle']);
    }
}
