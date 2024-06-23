<?php


/**
 * @author Flames <xenozf@gmail.com>
 * 
 * @copyright 2024 Flute
 */

namespace Flute\Core;

use Composer\Autoload\ClassLoader;
use DI\Definition\Helper\DefinitionHelper;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;

use Flute\Core\Contracts\ModuleServiceProviderInterface;
use Flute\Core\Contracts\ServiceProviderInterface;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\Support\FluteEventDispatcher;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Traits\LangTrait;
use Flute\Core\Traits\RouterTrait;
use Flute\Core\Traits\ContainerTrait;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Traits\ConfigurationTrait;
use Flute\Core\Traits\LoggerTrait;
use Flute\Core\Traits\ThemeTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

final class App
{
    use ContainerTrait,
        RouterTrait,
        ConfigurationTrait,
        ThemeTrait,
        LangTrait,
        LoggerTrait;

    public const PERFORMANCE_MODE = 'performance';
    public const DEFAULT_MODE = 'default';

    /**
     * It's a version of the Flute
     * 
     * @var string
     */
    public const VERSION = '0.2.1.7-dev';

    /**
     * Set the base path of the application
     * 
     * @var string
     */
    protected string $basePath = BASE_PATH;

    /**
     * The current globally available instance (if any).
     *
     * @var static
     */
    protected static App $instance;

    /**
     * @var array Service provers
     */
    protected array $providers = [];

    /**
     * @var array Events in $listen
     */
    protected array $listen = [];

    private \League\Config\ConfigurationInterface $configuration;
    private ClassLoader $loader;

    public function __construct(ClassLoader $loader)
    {
        $this->loader = $loader;

        $this->_setContainer();
    }

    /**
     * set the container instance
     * 
     * @return void
     */
    protected function _setContainer(): void
    {
        $containerBuilder = new \DI\ContainerBuilder();

        $containerBuilder->addDefinitions([
            self::class => $this,
            "app" => \DI\get(self::class)
        ]);

        $containerBuilder->enableCompilation(BASE_PATH . 'storage/app/cache');
        $containerBuilder->writeProxiesToFile(true, BASE_PATH . 'storage/app/proxies');

        $this->setContainerBuilder($containerBuilder);
    }

    /**
     * Get the autoload loader
     * 
     * @return ClassLoader
     */
    public function getLoader(): ClassLoader
    {
        return $this->loader;
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): App
    {
        if (is_null(self::$instance)) {
            self::$instance = new self(require BASE_PATH . 'vendor/autoload.php');
        }

        return self::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param App|null $app
     * @return App|static
     */
    public static function setInstance(App $app = null): ?App
    {
        return self::$instance = $app;
    }

    /**
     * Set the base path of the application
     * 
     * @param string $basePath
     * 
     * @return void
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;

        $this->containerBuilder->addDefinitions([
            'base_path' => $this->basePath,
        ]);
    }

    /**
     * Get the base path of the application
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set the value in the container
     * 
     * @param string $key
     * @param DefinitionHelper|mixed $value
     * 
     * @return self
     */
    public function bind(string $key, $value): App
    {
        $this->container->set($key, $value);

        return $this;
    }

    /**
     * Set debug mode
     * 
     * @param bool $debug
     * 
     * @return void
     */
    public function debug(bool $debug = true): void
    {
        if (function_exists('ini_set')) {
            ini_set('display_errors', (string) $debug);
            ini_set('display_startup_errors', (string) $debug);
        }

        error_reporting(E_ALL);

        // We add debug mode to the container
        $this->bind("debug", (string) $debug);
    }

    /**
     * Make class instance.
     * ALWAYS CREATES A NEW INSTANCE!!!
     *
     * @param string $abstract
     * @param array $parameters
     * @param bool $throwException
     *
     * @return mixed|string|void
     * @throws NotFoundException
     */
    public function make(string $abstract, array $parameters = [], bool $throwException = false)
    {
        try {
            return $this->container->make($abstract, $parameters);
        } catch (NotFoundException $e) {

            if (function_exists('logs'))
                logs()->emergency($e->getMessage());

            if ($throwException)
                throw $e;
        } catch (DependencyException $e) {
        }
    }

    /**
     * Returns an entry of the container by its name.
     *
     * @param mixed $id Identifier of the entry to look for.
     * @return mixed Returns the entry from the container corresponding to the provided identifier.
     * @throws DependencyException Error while resolving the entry.
     * @throws NotFoundException No entry found for the given name.
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Set the service provider
     * 
     * @param ServiceProviderInterface|ModuleServiceProviderInterface $provider
     * 
     * @return App
     */
    public function serviceProvider($provider): App
    {
        $this->providers[] = $provider;
        
        try {
            $provider->register(
                $provider instanceof ServiceProviderInterface ? $this->getContainerBuilder() : $this->getContainer()
            );
        } catch (Exception $e) {
            logs()->error($e);
        }

        return $this;
    }

    /**
     * Boot the all service providers
     * 
     * @return void
     */
    public function bootServiceProviders()
    {
        foreach ($this->providers as $key => $provider) {
            try {
                $provider->boot($this->container);

                $this->listen = array_merge_recursive($this->listen, $provider->getEventListeners());
            } catch (Exception $e) {
                logs()->error($e);
            }
        }

        $this->initializeEventListeners();
    }

    /**
     * Initialize and register event listeners from the $listen array.
     *
     * @return void
     */
    protected function initializeEventListeners(): void
    {
        /** @var FluteEventDispatcher $dispatcher */
        $dispatcher = $this->container->get(FluteEventDispatcher::class);

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->addListener($event, [new $listener, 'handle']);
            }
        }
    }

    /**
     * Get app version
     * 
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Run the app
     *
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function run(): void
    {
        // Я пока не буду тут вставлять редирект, потому что если модули начнут
        // инициализировать свои компоненты из БД, посыпяться ошибки,
        // поэтому можно сделать так, чтобы редирект происходил в сервис провайдере
        // установщика, т.к. только там еще не инициализируются модули и БД'шные штуки
        // $this->redirectIfNotInstalled();

        /** @var RouteDispatcher $router */
        $router = $this->get(RouteDispatcher::class);
        $res = $this->responseEvent($router->handle($this->get(FluteRequest::class)));

        $this->get(FluteEventDispatcher::class)->saveDeferredListenersToCache();

        // Ставим новый Response из ивента в возвращаемый объект.
        // Вдруг кто-то там что-то поменял, и нам надо вернуть новый объект
        if (is_debug() && !(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'))
            $res->sendContent();
        else
            $res->send();
    }

    /**
     * Build container and save
     *
     * @return void
     * @throws Exception
     */
    public function buildContainer(): void
    {
        $this->setContainer($this->containerBuilder->build());
    }

    /**
     * Function for handling all cookies
     *
     * @param Response $response
     *
     * @return Response
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function responseEvent(Response $response): Response
    {
        return $this->get(EventDispatcher::class)
            ->dispatch(new ResponseEvent($response), ResponseEvent::NAME)
            ->getResponse();
    }

    /**
     * Get app container values
     *
     * @param $name
     * @param $args
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __call($name, $args)
    {
        return $this->get($name);
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being serialized (which would create a second instance of it)
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot serialize singleton");
    }
}