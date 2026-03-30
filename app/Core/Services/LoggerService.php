<?php

namespace Flute\Core\Services;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

class LoggerService
{
    protected const MAX_FILES = 31;

    protected const CACHE_KEY = 'logger_maintenance';

    protected const CACHE_TTL = 86400;

    protected const DEFAULT_DYNAMIC_LEVEL = Logger::INFO;

    protected array $loggers = [];

    public function __construct(array $loggersConfig)
    {
        $loggersConfig['cron'] = [
            'path' => BASE_PATH . '/storage/logs/cron.log',
            'level' => 100,
        ];

        foreach ($loggersConfig as $name => $config) {
            $this->addLogger($name, $config['path'], $config['level']);
        }
    }

    public function addLogger(string $name, string $logFile, int $logLevel = Logger::DEBUG)
    {
        $logger = new Logger($name);

        $logger->pushProcessor(new IntrospectionProcessor());
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(self::crashReportProcessor());

        $handler = new RotatingFileHandler($logFile, self::MAX_FILES, $logLevel, true, 0o666, true);

        $lineFormatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
        );
        $handler->setFormatter($lineFormatter);

        $logger->pushHandler($handler);

        $this->loggers[$name] = $logger;
    }

    /**
     * @throws Exception
     */
    public function getLogger(string $name)
    {
        $name = $this->normalizeLoggerName($name);

        if (!isset($this->loggers[$name])) {
            $this->createDynamicLogger($name);
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
            cache()->callback(
                self::CACHE_KEY,
                function () {
                    $this->cleanupOldLogs();
                },
                self::CACHE_TTL,
            );
        }
    }

    protected function normalizeLoggerName(string $name): string
    {
        $name = strtolower(trim($name));

        return $name !== '' ? $name : 'flute';
    }

    /**
     * @return callable(LogRecord): LogRecord
     */
    private static function crashReportProcessor(): callable
    {
        return static function (LogRecord $record): LogRecord {
            if ($record->level->value < Level::Error->value) {
                return $record;
            }

            if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
                CrashReportService::capture($record->context['exception'], [
                    'source' => 'log.' . $record->channel,
                ]);

                return $record;
            }

            $text = trim($record->message);

            CrashReportService::capture(
                new \ErrorException(
                    $text,
                    0,
                    E_ERROR,
                    $record->extra['file'] ?? __FILE__,
                    (int) ( $record->extra['line'] ?? 0 ),
                ),
                ['source' => 'log.' . $record->channel],
            );

            return $record;
        };
    }

    protected function createDynamicLogger(string $name): void
    {
        $safeName = preg_replace('/[^a-z0-9._-]+/i', '_', $name) ?: 'flute';
        $safeName = substr($safeName, 0, 64);

        $logsDir = rtrim((string) path('storage/logs'), '/\\');
        if (!is_dir($logsDir) && !@mkdir($logsDir, 0o755, true) && !is_dir($logsDir)) {
            throw new Exception('Unable to create logs directory: ' . $logsDir);
        }

        $logFile = $logsDir . '/' . $safeName . '.log';
        $logLevel = (int) ( config('logging.dynamic_level') ?? self::DEFAULT_DYNAMIC_LEVEL );

        $this->addLogger($name, $logFile, $logLevel);
    }
}
