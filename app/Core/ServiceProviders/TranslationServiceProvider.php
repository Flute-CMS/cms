<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use Flute\Core\Services\LanguageService;

use Flute\Core\Support\AbstractServiceProvider;

class TranslationServiceProvider extends AbstractServiceProvider
{
    protected $service;

    /**
     * Register the services provided by this provider.
     */
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            LanguageService::class => \DI\autowire(),
            "translation" => \DI\factory(function (LanguageService $service, Container $container) {
                return $container->get(LanguageService::class)->getTranslator();
            }),
        ]);
    }

    /**
     * Add a listener to handle LangChangedEvent.
     */
    public function boot( Container $container ): void 
    {
        $container->get("translation");
    }
}
