<?php

namespace Flute\Core\Contracts;

interface ParserInterface
{
    /**
     * Parse the given array.
     *
     * @param array $array
     * @param ?string $id
     *
     * @return mixed
     */
    public function parse(array $array, string $id);
}
