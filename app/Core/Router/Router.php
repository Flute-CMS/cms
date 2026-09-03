<?php

namespace Flute\Core\Router;

use DI\Container;
use Flute\Core\Events\RoutingStartedEvent;
use Flute\Core\Modules\Auth\Middlewares\IsAuthenticatedMiddleware;
use Flute\Core\Modules\Auth\Middlewares\IsGuestMiddleware;
use Flute\Core\Modules\Page\Middlewares\PagePermissionsMiddleware;
use Flute\Core\Router\Concerns\HandlesAttributeRoutes;
use Flute\Core\Router\Concerns\HandlesMiddlewareResolution;
use Flute\Core\Router\Concerns\HandlesRouteDispatch;
use Flute\Core\Router\Concerns\HandlesRouteGroups;
use Flute\Core\Router\Concerns\HandlesRouteLookup;
use Flute\Core\Router\Concerns\HandlesRouteRegistration;
use Flute\Core\Router\Contracts\RouteInterface;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Router\Middlewares\BanCheckMiddleware;
use Flute\Core\Router\Middlewares\CanMiddleware;
use Flute\Core\Router\Middlewares\CsrfMiddleware;
use Flute\Core\Router\Middlewares\HtmxMiddleware;
use Flute\Core\Router\Middlewares\MaintenanceMiddleware;
use Flute\Core\Router\Middlewares\RateLimiterMiddleware;
use Flute\Core\Router\Middlewares\SiteModeMiddleware;
use Flute\Core\Router\Middlewares\TokenMiddleware;
use Flute\Core\Traits\MacroableTrait;
use Flute\Core\Traits\SingletonTrait;
use Symfony\Component\Routing\RouteCollection;

class Router implements RouterInterface
{
    use HandlesAttributeRoutes;
    use HandlesMiddlewareResolution;
    use HandlesRouteDispatch;
    use HandlesRouteGroups;
    use HandlesRouteLookup;
    use HandlesRouteRegistration;
    use MacroableTrait;
    use SingletonTrait;

    protected RouteCollection $routes;
    protected RouteCollection $compilableRoutes;
    protected RouteCollection $dynamicRoutes;
    protected RouteCollection $frontCompilableRoutes;
    protected RouteCollection $adminCompilableRoutes;
    protected RouteCollection $frontDynamicRoutes;
    protected RouteCollection $adminDynamicRoutes;

    protected Container $container;

    protected array $middlewareGroups = [
        'web' => ['csrf', 'throttle'],
        'api' => ['throttle', 'ban.check'],
        'default' => ['maintenance', 'throttle', 'ban.check'],
    ];

    protected array $groupStack = [];

    protected array $middlewareAliases = [
        'can' => CanMiddleware::class,
        'csrf' => CsrfMiddleware::class,
        'auth' => IsAuthenticatedMiddleware::class,
        'htmx' => HtmxMiddleware::class,
        'guest' => IsGuestMiddleware::class,
        'throttle' => RateLimiterMiddleware::class,
        'token' => TokenMiddleware::class,
        'ban.check' => BanCheckMiddleware::class,
        'maintenance' => MaintenanceMiddleware::class,
        'page.permissions' => PagePermissionsMiddleware::class,
        'site_mode' => SiteModeMiddleware::class,
    ];

    protected ?RouteInterface $currentRoute = null;
    protected array $registeredDynamicRoutes = [];
    protected ?array $routePathIndex = null;

    public function __construct(Container $container)
    {
        $this->routes = new RouteCollection();
        $this->compilableRoutes = new RouteCollection();
        $this->dynamicRoutes = new RouteCollection();
        $this->frontCompilableRoutes = new RouteCollection();
        $this->adminCompilableRoutes = new RouteCollection();
        $this->frontDynamicRoutes = new RouteCollection();
        $this->adminDynamicRoutes = new RouteCollection();
        $this->container = $container;

        self::$instance = $this;

        events()->dispatch(new RoutingStartedEvent($this), RoutingStartedEvent::NAME);
    }

    public function getCurrentRoute(): ?RouteInterface
    {
        return $this->currentRoute;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}
