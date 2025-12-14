<?php

namespace Flute\Core\Router\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Put extends Route
{
    /**
     * Constructor for the Put route attribute
     *
     * @param string $uri The URI pattern for this route
     * @param string|null $name Optional route name for URL generation
     * @param array|string|null $middleware Optional middleware to be applied
     * @param array $where Optional parameter constraints
     * @param array $defaults Optional default values for route parameters
     */
    public function __construct(
        string $uri,
        ?string $name = null,
        array|string|null $middleware = null,
        array $where = [],
        array $defaults = []
    ) {
        parent::__construct($uri, ['PUT'], $name, $middleware, $where, $defaults);
    }
}
