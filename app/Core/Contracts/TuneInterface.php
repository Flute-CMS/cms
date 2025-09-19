<?php

namespace Flute\Core\Contracts;

interface TuneInterface
{
    /**
     * Parse the given array.
     *
     * @param string $compiled the compiled string from block parser
     *
     * @return mixed
     */
    public function parse($tune, string $compiled);
}
