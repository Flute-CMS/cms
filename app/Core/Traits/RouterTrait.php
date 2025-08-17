<?php

namespace Flute\Core\Traits;

use Flute\Core\App;
use Flute\Core\Router\Contracts\RouterInterface;

trait RouterTrait
{
    /**
     * @var RouterInterface
     */
    protected RouterInterface $router;

    /**
     * Set router instance
     *
     * @param RouterInterface $router
     * @return App
     */
    public function setRouter(RouterInterface $router): self
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Get router instance
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
