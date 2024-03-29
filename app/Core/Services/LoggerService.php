<?php

namespace Flute\Core\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerService
{
    protected array $loggers = [];

    public function __construct(array $loggersConfig)
    {
        foreach ($loggersConfig as $name => $config) {
            $this->addLogger($name, $config['path'], $config['level']);
        }
    }

    public function addLogger(string $name, string $logFile, int $logLevel = Logger::DEBUG)
    {
        $logger = new Logger($name);
        $logger->pushHandler(new StreamHandler($logFile, $logLevel));
        
        $this->loggers[$name] = $logger;
    }

    /**
     * @throws \Exception
     */
    public function getLogger(string $name)
    {
        if (!isset($this->loggers[$name])) {
            throw new \Exception("Logger $name is not found");
        }
        
        return $this->loggers[$name];
    }

    public function getLoggersNames(): array
    {
        return array_keys($this->loggers);
    }
}
