<?php

namespace Flute\Core\Contracts;

interface ParserInterface
{
    /**
     * Parse the given array.
     *
     * @param ?string $id
     *
     * @return mixed
     */
    public function parse(array $array, string $id);
}
