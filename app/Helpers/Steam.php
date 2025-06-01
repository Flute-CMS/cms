<?php

use Flute\Core\Services\SteamService;

if (!function_exists('steam')) {
    function steam() : SteamService
    {
        return app(SteamService::class);
    }
}
