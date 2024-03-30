<?php


namespace Flute\Core\ServiceProviders;


use Flute\Core\Services\ConfigurationService;
use DI\ContainerBuilder;
use Flute\Core\Support\AbstractServiceProvider;

class ConfigurationServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ConfigurationService::class => \DI\create(ConfigurationService::class)->constructor(BASE_PATH . '/config'),
            'configs' => \DI\get(ConfigurationService::class)
        ]);
    }

    public function boot(\DI\Container $container) : void
    {
        $configurationService = $container->get(ConfigurationService::class);
        $configs = $configurationService->toArray();
        $this->registerConfigServices($configs, $container);

        app()->debug($configs['app']['debug']);
        app()->setLang($configs['lang']['locale']);

        date_default_timezone_set($configs['app']['timezone']);
    }

    private function registerConfigServices(array $configs, \DI\Container $container, string $prefix = ''): void
    {
        foreach ($configs as $configKey => $configValue) {
            $serviceId = $prefix ? $prefix . '.' . $configKey : $configKey;

            if (is_array($configValue)) {
                $this->registerConfigServices($configValue, $container, $serviceId);
            }
            
            $container->set($serviceId, $configValue);
        }
    }
}
