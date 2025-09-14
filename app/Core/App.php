<?php


/**
 * @author Flames <xenozf@gmail.com>
 *
 * @copyright 2025 Flute
 */

namespace Flute\Core;

use Composer\Autoload\ClassLoader;
use DI\Definition\Helper\DefinitionHelper;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Flute\Core\Contracts\ServiceProviderInterface;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\ModulesManager\Contracts\ModuleServiceProviderInterface;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Support\FluteEventDispatcher;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Traits\ContainerTrait;
use Flute\Core\Traits\LangTrait;
use Flute\Core\Traits\LoggerTrait;
use Flute\Core\Traits\RouterTrait;
use Flute\Core\Traits\SingletonTrait;
use Flute\Core\Traits\ThemeTrait;
use Symfony\Component\Console\Application;
use Flute\Core\Profiling\GlobalProfiler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

final class App
{
    use ContainerTrait;
    use RouterTrait;
    use ThemeTrait;
    use LangTrait;
    use LoggerTrait;
    use SingletonTrait;

    public const PERFORMANCE_MODE = 'performance';
    public const DEFAULT_MODE = 'default';

    /**
     * @var string
     */
    public const VERSION = '0.1.8.3';

    /**
     * Set the base path of the application
     *
     * @var string
     */
    protected string $basePath = BASE_PATH;

    /**
     * @var array<ServiceProviderInterface>
     */
    protected array $providers = [];

    /**
     * @var array Events in
     */
    protected array $listen = [];

    private ClassLoader $loader;

    private bool $isBooted = false;

    /**
     * @var Application|null
     */
    protected ?Application $consoleApplication = null;

    protected array $bootTimes = [];

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
            "app" => \DI\get(self::class),
        ]);

        // Enable container optimizations outside CLI
        if (!(php_sapi_name() === 'cli' || defined('STDIN'))) {
            // In performance mode compile container, always write proxies to disk
            if (function_exists('is_performance') && is_performance()) {
                $containerBuilder->enableCompilation(BASE_PATH . 'storage/app/cache');
            }
            $containerBuilder->writeProxiesToFile(true, BASE_PATH . 'storage/app/proxies');
        }

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
    public static function setInstance(?App $app = null): ?App
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

            if (function_exists('logs')) {
                logs()->emergency($e->getMessage());
            }

            if ($throwException) {
                throw $e;
            }
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
     * Determine if the container has a given entry.
     *
     * This is a lightweight proxy to the underlying PHP-DI container's
     * has() method and allows calling code to use app()->has(Foo::class)
     * safely without triggering the __call magic method.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool True if the container can return an entry for the given identifier, otherwise false.
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Set the service provider
     *
     * @param ServiceProviderInterface|ModuleServiceProviderInterface|string $provider
     *
     * @return App
     */
    public function serviceProvider($provider): App
    {
        $provider = is_string($provider) ? new $provider() : $provider;

        $this->providers[] = $provider;

        try {
            $provider->register(
                $provider instanceof ServiceProviderInterface ? $this->getContainerBuilder() : $this->getContainer()
            );
        } catch (Exception $e) {
            if (function_exists('is_debug') && is_debug()) {
                throw $e;
            }

            if (function_exists('logs')) {
                logs()->error($e);
            }
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
        if ($this->isBooted) {
            return;
        }

        foreach ($this->providers as $key => $provider) {
            $startTime = microtime(true);

            try {
                $className = get_class($provider);

                $provider->setApp($this);
                $provider->boot($this->container);
                $this->listen = array_merge_recursive($this->listen, $provider->getEventListeners());

                $this->bootTimes[$className] = round(microtime(true) - $startTime, 3);
            } catch (Exception $e) {
                if (function_exists('is_debug') && is_debug()) {
                    throw $e;
                }

                if (function_exists('logs')) {
                    logs()->error($e);
                }
            }
        }

        $this->initializeEventListeners();

        $this->isBooted = true;
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
                $dispatcher->addListener($event, [new $listener(), 'handle']);
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
     * @return void
     * @throws \Exception
     */
    public function runCli(): void
    {
        $console = $this->getConsole();

        $console->run();
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
        if (is_cli()) {
            $this->runCli();

            return;
        }

        if (!defined('FLUTE_ROUTER_START')) {
            define('FLUTE_ROUTER_START', microtime(true));
        }

        // Optional global profiler (only runs if enabled in config/profiler.php)
        GlobalProfiler::start();

        $this->get(DatabaseConnection::class)->recompileIfNeeded();
        if (!defined('FLUTE_DB_SETUP_END')) {
            define('FLUTE_DB_SETUP_END', microtime(true));
        }

        /** @var RouterInterface $router */
        $router = $this->get(RouterInterface::class);

        // Split routing and event phases to measure them separately
        $request = $this->get(FluteRequest::class);
        $dispatchResult = $router->dispatch($request);
        if (!defined('FLUTE_DISPATCH_END')) {
            define('FLUTE_DISPATCH_END', microtime(true));
        }

        $res = $this->responseEvent($dispatchResult);
        if (!defined('FLUTE_EVENTS_END')) {
            define('FLUTE_EVENTS_END', microtime(true));
        }

        if (!defined('FLUTE_ROUTER_END')) {
            define('FLUTE_ROUTER_END', microtime(true));
        }

        $this->get(FluteEventDispatcher::class)->saveDeferredListenersToCache();
        if (!defined('FLUTE_DEFERRED_SAVE_END')) {
            define('FLUTE_DEFERRED_SAVE_END', microtime(true));
        }

        // Stop profiler before sending response
        GlobalProfiler::stop();

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
     * @return Application
     */
    public function getConsole(): Application
    {
        if ($this->consoleApplication === null) {
            $this->consoleApplication = new Application('Flute CLI', self::VERSION);
            $this->bind(Application::class, $this->consoleApplication);
            $this->bind('console', $this->consoleApplication);
        }

        return $this->consoleApplication;
    }

    /**
     * Get the boot times
     *
     * @return array
     */
    public function getBootTimes(): array
    {
        return $this->bootTimes;
    }

    /**
     * Get the boot time
     *
     * @param string $key
     *
     * @return int
     */
    public function getBootTime(string $key): int
    {
        return $this->bootTimes[$key] ?? 0;
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
}
