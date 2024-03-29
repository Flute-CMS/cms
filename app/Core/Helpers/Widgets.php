<?php

use Flute\Core\Widgets\WidgetManager;

if (!function_exists('widgets')) {
    function widgets() : WidgetManager
    {
        return app(WidgetManager::class);
    }
}
