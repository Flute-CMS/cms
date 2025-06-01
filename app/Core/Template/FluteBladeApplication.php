<?php

namespace Flute\Core\Template;

use Illuminate\Container\Container;

class FluteBladeApplication extends Container
{
    protected $terminatingCallbacks = [];

    public function getNamespace()
    {
        return '';
    }

    /**
     * Register a terminating callback with the application.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function terminating($callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate()
    {
        foreach ($this->terminatingCallbacks as $callback) {
            call_user_func($callback);
        }
    }
}
