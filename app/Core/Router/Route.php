<?php

namespace Flute\Core\Router;

class Route
{
    private string $method;
    private string $pattern;
    private $handler;

    /**
     * Constructor for Route.
     * 
     * @param string $method  The HTTP method for the route.
     * @param string $pattern The path pattern for the route.
     * @param mixed  $handler The handler for the route.
     */
    public function __construct(string $method, string $pattern, $handler)
    {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->handler = $handler;
    }

    /**
     * Gets the HTTP method for the route.
     * 
     * @return string The HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the path pattern for the route.
     * 
     * @return string The path pattern.
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Gets the handler for the route.
     * 
     * @return mixed The handler.
     */
    public function getHandler()
    {
        return $this->handler;
    }
}
