<?php

use Flute\Core\Table\TableBuilder;

if (!function_exists("table")) {
    function table(string $ajaxPath = null, string $section = null) : TableBuilder
    {
        return new TableBuilder($ajaxPath, $section);
    }
}
