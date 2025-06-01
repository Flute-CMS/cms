<?php

namespace Flute\Core\Logging\Contracts;

use Flute\Core\Database\Entities\UserActionLog;

interface LogFormatterInterface
{
    /**
     * Determines if the formatter is suitable for the log
     */
    public function supports(UserActionLog $log) : bool;

    /**
     * Returns HTML (or string), which we will display
     */
    public function render(UserActionLog $log) : string;
}
