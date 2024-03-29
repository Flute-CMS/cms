<?php
use Flute\Core\Support\Collection;


if (!function_exists("collect")) 
{
    /**
     * Get the collection instance
     * 
     * @return Collection
     */
    function collect(array $elements  = [])
    {
        return new Collection;
    }
}
