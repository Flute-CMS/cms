<?php

namespace Flute\Core\TracyBar;

use Flute\Core\App;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\Debugger;

class FluteTracyBar
{
    /**  */
    protected App $app;

    protected $version;

    protected $startTime;

    public function __construct(?App $app = null)
    {
        $this->app = $app ?? app();
        $this->startTime = defined('FLUTE_START') ? FLUTE_START : microtime(true);
        $this->version = $this->app->getVersion();

        $tracyLogger = new PsrToTracyLoggerAdapter(logs());

        Debugger::$dumpTheme = 'dark';
        Debugger::$maxDepth = 3;
        Debugger::$showLocation = false;

        Debugger::setLogger($tracyLogger);

        // When behind a reverse proxy (Cloudflare, nginx, etc.), Tracy sees
        // the proxy IP in REMOTE_ADDR and silently switches to Production mode
        // because it thinks the request is not from a trusted developer.
        // We already handle access control via is_debug(), so we override
        // Tracy's IP detection by temporarily setting REMOTE_ADDR to localhost.
        $origRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        Debugger::enable($this->getDebugAccess());

        // Restore the real REMOTE_ADDR so the rest of the app sees it correctly.
        if ($origRemoteAddr !== null) {
            $_SERVER['REMOTE_ADDR'] = $origRemoteAddr;
        } else {
            unset($_SERVER['REMOTE_ADDR']);
        }

        $this->addPanels();
    }

    /**
     * Adds a message to Tracy's logger
     *
     * @param mixed $message
     */
    public function addMessage($message, string $priority = 'info')
    {
        Debugger::log($message, $priority);
    }

    /**
     * Get total execution time
     */
    public function getExecutionTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    protected function addPanels()
    {
        Debugger::getBar()->addPanel(new ModulesTimingPanel());
    }

    protected function getDebugAccess(): int
    {
        return Debugger::Development;
    }
}
