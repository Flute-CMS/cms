<?php

use Flute\Core\Auth\SocialService;

if (!function_exists('social')) {
    function social() : SocialService
    {
        return app(SocialService::class);
    }
}
