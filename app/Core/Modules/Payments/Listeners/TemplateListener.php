<?php

namespace Flute\Core\Modules\Payments\Listeners;

use Flute\Core\Modules\Payments\Components\PaymentComponent;
use Flute\Core\Modules\Payments\Initializers\GatewayInitializer;
use Flute\Core\Modules\Payments\Services\PaymentsCleaner;

class TemplateListener
{
    public static function handle(\Flute\Core\Template\Events\TemplateInitialized $event): void
    {
        $event->getTemplate()->registerComponent('payment-form', PaymentComponent::class);

        try {
            app()->get(GatewayInitializer::class);
            app()->get(PaymentsCleaner::class)->cleanOldPayments();
        } catch (\Throwable $e) {
            logs('modules')->error('Payments init error: ' . $e->getMessage());
        }
    }
}
