<?php

namespace Flute\Core\Modules\Profile\Providers;

use Flute\Core\Modules\Profile\Listeners\TemplateListener;
use Flute\Core\Modules\Profile\Services\ProfileEditTabService;
use Flute\Core\Modules\Profile\Services\ProfileTabService;
use Flute\Core\Modules\Profile\Tabs\Edit\MainTab;
use Flute\Core\Modules\Profile\Tabs\Edit\PaymentsTab;
use Flute\Core\Modules\Profile\Tabs\Edit\SocialTab;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Template\Events\TemplateInitialized;

class ProfileServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ProfileTabService::class => \DI\create(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_installed()) {
            $this->loadRoutesFrom(cms_path('Profile/Routes/profile.php'));

            // REGISTER MAIN TABS FOR EDIT
            $profileEditTab = $container->get(ProfileEditTabService::class);
            $profileEditTab->register(new MainTab());
            $profileEditTab->register(new SocialTab());
            $profileEditTab->register(new PaymentsTab());
            // ---------------------------

            events()->addDeferredListener(TemplateInitialized::NAME, [TemplateListener::class, 'handle']);
        }
    }
}
