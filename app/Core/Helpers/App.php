<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\App;

if (!function_exists("app")) {
    /**
     * Get the instance of the Application class or resolve a class/interface from the container.
     *
     * @template T
     * @param class-string<T>|null $name Entry name or a class name.
     *
     * @return ($name is null ? App : T) Returns an instance of the type specified by $name, or the Application instance if $name is null.
     * @throws DependencyException
     * @throws NotFoundException
     */
    function app($name = null)
    {
        // Get the instance of the Application class
        $app = App::getInstance();

        // If no argument is passed, return the Application instance itself
        if (is_null($name)) {
            return $app;
        }

        // If an argument is provided, resolve the given class or interface from the container
        return $app->get($name);
    }
}