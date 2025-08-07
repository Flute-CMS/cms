<?php

namespace Flute\Core\Traits;

use Flute\Core\App;
use Monolog\Logger;

trait LoggerTrait
{
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
    * Set logger factory instance
    *
    * @param Logger $logger
    * @return App
    */
    public function setLogger(Logger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger factory instance
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
