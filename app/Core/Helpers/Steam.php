<?php

use Flute\Core\Steam\SteamParser;

if (!function_exists('steam')) {
    function steam() : SteamParser
    {
        return app(SteamParser::class);
    }
}
