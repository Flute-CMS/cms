<?php

namespace Flute\Core\Events;

use Flute\Core\Router\RouteDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

class RoutingStartedEvent extends Event
{
    public const NAME = 'routing.started';

    private RouteDispatcher $router;

    public function __construct(RouteDispatcher $router)
    {
        $this->router = $router;
    }

    public function getRouteDispatcher(): RouteDispatcher
    {
        return $this->router;
    }
}
