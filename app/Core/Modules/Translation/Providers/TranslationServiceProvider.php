<?php

namespace Flute\Core\Modules\Translation\Providers;

use DI\Container;
use Flute\Core\Modules\Translation\Services\TranslationService;
use Flute\Core\Support\AbstractServiceProvider;

class TranslationServiceProvider extends AbstractServiceProvider
{
    protected $service;

    /**
     * Register the services provided by this provider.
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            TranslationService::class => \DI\autowire(),
            "translation" => \DI\factory(function (TranslationService $service, Container $container) {
                return $container->get(TranslationService::class)->getTranslator();
            }),
        ]);
    }

    /**
     * Add a listener to handle LangChangedEvent.
     */
    public function boot(Container $container): void
    {
        $container->get(TranslationService::class);

        $this->loadRoutesFrom(cms_path('Translation/Routes/translation.php'));
    }
}
