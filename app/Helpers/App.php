<?php

use Flute\Core\App;

if (!function_exists("app")) {
    /**
     * @param  string|null  $abstract
     * @param  array  $parameters
     * @return App|mixed
     */
    function app($name = null)
    {
        $app = App::getInstance();

        if (is_null($name)) {
            return $app;
        }

        return $app->get($name);
    }
}