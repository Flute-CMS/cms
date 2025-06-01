<?php

namespace Flute\Core\Traits;

trait SingletonTrait
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct() {}
    public function __clone() {}
    public function __wakeup() {}
}
