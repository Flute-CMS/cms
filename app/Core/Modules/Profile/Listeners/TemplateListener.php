<?php

namespace Flute\Core\Modules\Profile\Listeners;

use Flute\Core\Modules\Profile\Components\DeleteAccountComponent;
use Flute\Core\Modules\Profile\Components\EditMainComponent;
use Flute\Core\Modules\Profile\Components\EditNotificationsComponent;
use Flute\Core\Modules\Profile\Components\EditSocialsComponent;
use Flute\Core\Modules\Profile\Components\TableBalanceHistoryComponent;
use Flute\Core\Modules\Profile\Components\TablePaymentsComponent;
use Flute\Core\Modules\Profile\Components\TwoFactorComponent;

class TemplateListener
{
    public static function handle(\Flute\Core\Template\Events\TemplateInitialized $event): void
    {
        $template = $event->getTemplate();

        $template->registerComponent('profile-edit-main', EditMainComponent::class);
        $template->registerComponent('profile-edit-notifications', EditNotificationsComponent::class);
        $template->registerComponent('profile-two-factor', TwoFactorComponent::class);
        $template->registerComponent('profile-edit-socials', EditSocialsComponent::class);
        $template->registerComponent('table-payments', TablePaymentsComponent::class);
        $template->registerComponent('table-balance-history', TableBalanceHistoryComponent::class);
        $template->registerComponent('profile-delete-account', DeleteAccountComponent::class);
    }
}
