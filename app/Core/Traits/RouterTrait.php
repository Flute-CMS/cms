<?php

namespace Flute\Core\Traits;

use Flute\Core\App;
use Flute\Core\Router\RouteDispatcher;

trait RouterTrait
{
    /**
     * @var RouteDispatcher
     */
    protected RouteDispatcher $router;
    
    /**
     * Set router instance
     * 
     * @param RouteDispatcher $router
     * @return App
     */
    public function setRouter(RouteDispatcher $router): self
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Get router instance
     * 
     * @return RouteDispatcher
     */
    public function getRouter(): RouteDispatcher
    {
        return $this->router;
    }
}