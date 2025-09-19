<?php

namespace Flute\Core\Traits;

use Flute\Core\App;
use Flute\Core\Router\Contracts\RouterInterface;

trait RouterTrait
{
    /**
     */
    protected RouterInterface $router;

    /**
     * Set router instance
     *
     * @return App
     */
    public function setRouter(RouterInterface $router): self
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Get router instance
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
