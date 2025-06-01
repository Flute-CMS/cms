<?php

namespace Flute\Core\Services;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

class LoggerService
{
    protected array $loggers = [];
    protected const MAX_FILES = 31;
    protected const CACHE_KEY = 'logger_maintenance';
    protected const CACHE_TTL = 86400;

    public function __construct(array $loggersConfig)
    {
        foreach ($loggersConfig as $name => $config) {
            $this->addLogger($name, $config['path'], $config['level']);
        }
    }

    public function addLogger(string $name, string $logFile, int $logLevel = Logger::DEBUG)
    {
        $logger = new Logger($name);
        
        $logger->pushProcessor(new IntrospectionProcessor());
        $logger->pushProcessor(new WebProcessor());
        
        $handler = new RotatingFileHandler(
            $logFile, 
            self::MAX_FILES, 
            $logLevel,
            true,
            0666,
            true
        );
        
        $lineFormatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        );
        $handler->setFormatter($lineFormatter);
        
        $logger->pushHandler($handler);
        
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
    
    /**
     * Manually clean up old log files
     * This can be called from a cron job
     */
    public function cleanupOldLogs(): void
    {
        foreach ($this->loggers as $name => $logger) {
            $handlers = $logger->getHandlers();
            foreach ($handlers as $handler) {
                if ($handler instanceof RotatingFileHandler) {
                    $handler->close();
                    $logger->info('Log rotation triggered by cleanup job');
                }
            }
        }
    }
    
    /**
     * Setup cron job for log maintenance
     */
    public function setupCron(): void
    {
        if (config('app.cron_mode')) {
            scheduler()->call(function () {
                $this->cleanupOldLogs();
            })->daily();
        } else {
            cache()->callback(self::CACHE_KEY, function () {
                $this->cleanupOldLogs();
            }, self::CACHE_TTL);
        }
    }
}
