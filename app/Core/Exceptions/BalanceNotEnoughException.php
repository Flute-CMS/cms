<?php

namespace Flute\Core\Exceptions;

use Exception;

class BalanceNotEnoughException extends Exception
{
    protected int $need = 0;

    public function setNeededSum(int $amount): BalanceNotEnoughException
    {
        $this->need = $amount;

        return $this;
    }

    public function getNeededSum(): int
    {
        return $this->need;
    }
}
