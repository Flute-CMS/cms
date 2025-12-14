<?php

namespace Flute\Core\Support;

use Clickfwd\Yoyo\Yoyo;
use Exception;
use Flute\Core\App;
use Flute\Core\Contracts\ServiceProviderInterface;
use Flute\Core\Router\Contracts\RouterInterface;

/**
 * Abstract for easy integration in ServiceProviders.
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    protected $listen = [];

    /**
     * The application instance.
     *
     * @var App
     */
    protected $app;

    /**
     * Create a new service provider instance.
     */
    public function setApp(App $app): void
    {
        $this->app = $app;
    }

    /**
     * Get the event listeners.
     */
    public function getEventListeners(): array
    {
        return $this->listen;
    }

    /**
     * Load routes from a given path.
     */
    public function loadRoutesFrom(string $relativePath): void
    {
        $basePath = $this->app->getBasePath();
        $fullPath = $basePath . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);

        // global view
        $router = $this->app->make(RouterInterface::class);

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            throw new Exception("Routes from {$relativePath} wasn't found");
        }
    }

    /**
     * Add a namespace to the template engine.
     *
     * @param string|array $hints
     */
    public function addNamespace(string $namespace, $hints): void
    {
        $template = template();

        if ($template) {
            $template->addNamespace($namespace, $hints);
        }
    }

    public function registerComponents(array $components)
    {
        Yoyo::registerComponents($components);
    }

    /**
     * Register services with the container builder.
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
    }

    /**
     * Boot services with the container.
     */
    public function boot(\DI\Container $container): void
    {
    }
}
