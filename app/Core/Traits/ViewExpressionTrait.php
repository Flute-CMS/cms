<?php

namespace Flute\Core\Traits;

use Exception;

trait ViewExpressionTrait
{
    /**
     * Convert a given blade to html
     *
     * @param string $html
     *
     * @return string
     * @throws Exception
     */
    protected function convertToViewExpression(string $html): string
    {
        return template()->runString($html);
    }
}
