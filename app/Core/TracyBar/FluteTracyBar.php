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
        Debugger::enable($this->getDebugAccess());

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
        Debugger::getBar()
            ->addPanel(new ModulesTimingPanel());
    }

    protected function getDebugAccess()
    {
        if (sizeof(config('app.debug_ips')) > 0) {
            return config('app.debug_ips');
        }

        if (!is_debug()) {
            return Debugger::Production;
        }

        return Debugger::Development;
    }
}
