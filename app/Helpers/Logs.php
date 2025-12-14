<?php

use Flute\Core\Services\LoggerService;
use Monolog\Logger;

if (!function_exists("logs")) {
    /**
     * Returns the logger service
     * 
     * @param string $name - Name of the logger service
     *
     * @return Logger
     */
    function logs(string $name = "flute"): Logger
    {
        /**
         * @var LoggerService
         */
        $logger = app(LoggerService::class);
        
        return $logger->getLogger($name);
    }
}
