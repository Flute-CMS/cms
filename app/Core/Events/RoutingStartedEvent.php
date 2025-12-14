<?php

namespace Flute\Core\Events;

use Flute\Core\Router\Contracts\RouterInterface;
use Symfony\Contracts\EventDispatcher\Event;

class RoutingStartedEvent extends Event
{
    public const NAME = 'routing.started';

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getRouteDispatcher(): RouterInterface
    {
        return $this->router;
    }
}
