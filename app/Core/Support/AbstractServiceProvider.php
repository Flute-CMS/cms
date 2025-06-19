<?php

namespace Flute\Core\Support;

use Clickfwd\Yoyo\Yoyo;
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
     *
     * @param App $app
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * Get the event listeners.
     *
     * @return array
     */
    public function getEventListeners(): array
    {
        return $this->listen;
    }

    /**
     * Load routes from a given path.
     *
     * @param string $relativePath
     * @return void
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
            throw new \Exception("Routes from {$relativePath} wasn't found");
        }
    }

    /**
     * Add a namespace to the template engine.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return void
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
     *
     * @param \DI\ContainerBuilder $containerBuilder
     * @return void
     */
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
    }

    /**
     * Boot services with the container.
     *
     * @param \DI\Container $container
     * @return void
     */
    public function boot(\DI\Container $container): void
    {
    }
}
