<?php

namespace Flute\Core\Exceptions;

use Exception;

class RequestValidateException extends Exception
{
    protected array $errors = [];

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
