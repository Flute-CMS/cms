<?php

namespace Flute\Core\Exceptions;

use Exception;

class TooManyRequestsException extends Exception
{
    private int $estimatedWaitingTime;

    /**
     * Create a new TooManyRequestsException instance.
     *
     * @param string $message              The exception message.
     * @param int    $estimatedWaitingTime The estimated waiting time in seconds.
     * @param int    $code                 The exception code.
     * @param Exception|null $previous     The previous exception used for chaining.
     */
    public function __construct(
        string $message = 'Too Many Requests',
        int $estimatedWaitingTime = 0,
        int $code = 0,
        ?Exception $previous = null
    ) {
        $this->estimatedWaitingTime = $estimatedWaitingTime;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the estimated waiting time in seconds.
     *
     * @return int The estimated waiting time in seconds.
     */
    public function getEstimatedWaitingTime(): int
    {
        return $this->estimatedWaitingTime;
    }
}
