<?php

namespace Flute\Core\Modules\Payments\Exceptions;

use Exception;

class PaymentValidationException extends Exception
{
    protected $field;

    public function __construct(string $message, string $field)
    {
        parent::__construct($message);
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
