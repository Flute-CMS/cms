<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\ServiceProviderInterface;

/**
 * Abstract for easy integration in ServiceProviders.
 */

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    protected $listen = [];

    public function getEventListeners(): array
    {
        return $this->listen;
    }

    public function loadRoutesFrom(string $path)
    {
        // mb temporarly
        $router = router();

        require path($path);
    }
}