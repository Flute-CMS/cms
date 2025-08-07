<?php

namespace Flute\Core\Traits;

trait Makeable
{
    public static function make(?string $name = null): self
    {
        return (new static())->name($name);
    }
}
