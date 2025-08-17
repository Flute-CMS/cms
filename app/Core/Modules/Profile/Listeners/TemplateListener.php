<?php

namespace Flute\Core\Modules\Profile\Listeners;

use Flute\Core\Modules\Profile\Components\EditMainComponent;
use Flute\Core\Modules\Profile\Components\EditSocialsComponent;
use Flute\Core\Modules\Profile\Components\TablePaymentsComponent;

class TemplateListener
{
    public static function handle(\Flute\Core\Template\Events\TemplateInitialized $event): void
    {
        $template = $event->getTemplate();

        $template->registerComponent('profile-edit-main', EditMainComponent::class);
        $template->registerComponent('profile-edit-socials', EditSocialsComponent::class);
        $template->registerComponent('table-payments', TablePaymentsComponent::class);
    }
}
