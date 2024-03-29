<?php

namespace Flute\Core\TracyBar;

use Nette\Bridges\HttpTracy\SessionPanel;
use Tracy\Bridges\Psr\PsrToTracyLoggerAdapter;
use Tracy\Debugger;
use Flute\Core\App;

class FluteTracyBar
{
    /** @var App $app */
    protected App $app;
    protected $version;

    public function __construct(?App $app = null)
    {
        $this->app = $app ?? app();

        $this->version = $this->app->getVersion();

        $tracyLogger = new PsrToTracyLoggerAdapter(logs());

        Debugger::$dumpTheme = 'dark';
        Debugger::setLogger($tracyLogger);
        Debugger::enable($this->getDebugAccess());

        $this->addPanels();
    }

    protected function addPanels()
    {
        Debugger::getBar()
            ->addPanel(new SessionPanel());
        // ->addPanel(new Panel);
    }

    /**
     * Adds a message to Tracy's logger
     * 
     * @param mixed $message
     * @param string $priority
     */
    public function addMessage($message, string $priority = 'info')
    {
        Debugger::log($message, $priority);
    }

    protected function getDebugAccess()
    {
        if( sizeof( config('app.debug_ips') ) > 0 )
            return config('app.debug_ips');
        
        if( !is_debug() )
            return Debugger::Production;

        return Debugger::Development;
    }
}
