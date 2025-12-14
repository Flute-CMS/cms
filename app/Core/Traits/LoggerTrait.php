<?php

namespace Flute\Core\Traits;

use Flute\Core\App;
use Monolog\Logger;

trait LoggerTrait
{
    /**
     */
    protected Logger $logger;

    /**
    * Set logger factory instance
    *
    * @return App
    */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger factory instance
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
