<?php

namespace Flute\Core\Logging;

use Flute\Core\Database\Entities\UserActionLog;
use Flute\Core\Logging\Contracts\LogFormatterInterface;
use Flute\Core\Logging\Renderer\DefaultLogFormatter;

class LogRendererManager
{
    /**
     * @var LogFormatterInterface[]
     */
    private array $formatters = [];

    private DefaultLogFormatter $defaultFormatter;

    public function __construct(
        DefaultLogFormatter $defaultFormatter
    ) {
        $this->defaultFormatter = $defaultFormatter;
    }

    /**
     * Add a formatter to the manager
     * 
     * @param LogFormatterInterface $formatter
     */
    public function addFormatter(LogFormatterInterface $formatter) : void
    {
        $this->formatters[] = $formatter;
    }

    /**
     * Returns the rendered log
     * 
     * @param UserActionLog $log
     * 
     * @return string
     */
    public function render(UserActionLog $log) : string
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($log)) {
                return $formatter->render($log);
            }
        }

        return $this->defaultFormatter->render($log);
    }
}
