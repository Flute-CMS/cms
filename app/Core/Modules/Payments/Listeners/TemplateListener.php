<?php

namespace Flute\Core\Modules\Payments\Listeners;

use Flute\Core\Modules\Payments\Components\PaymentComponent;

class TemplateListener
{
    public static function handle(\Flute\Core\Template\Events\TemplateInitialized $event): void
    {
        $event->getTemplate()->registerComponent('payment-form', PaymentComponent::class);
    }
}
