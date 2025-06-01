<?php

namespace Flute\Core\Modules\Page\Providers;

use Flute\Core\Modules\Page\Services\PageManager;
use Flute\Core\Modules\Page\Services\WidgetManager;
use Flute\Core\Support\AbstractServiceProvider;
use DI\ContainerBuilder;
use DI\Container;

class PageServiceProvider extends AbstractServiceProvider
{
    /**
     * Registers definitions into the DI container.
     *
     * @param ContainerBuilder $containerBuilder The container builder.
     */
    public function register(ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            PageManager::class => \DI\autowire(),
            WidgetManager::class => \DI\autowire(),
            'widgets' => \DI\get(WidgetManager::class)
        ]);
    }

    /**
     * Boots the service provider.
     *
     * @param Container $container The DI container.
     */
    public function boot(Container $container) : void
    {
        if (is_installed()) {
            $this->loadRoutesFrom(cms_path('Page/Routes/page.php'));

            /** @var WidgetManager $widgetManager */
            $widgetManager = $container->get(WidgetManager::class);
            $widgetManager->registerDefaultWidgets();

            $container->get(PageManager::class);
        }
    }
}
