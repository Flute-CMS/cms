<?php

use Flute\Core\Modules\Auth\Services\SocialService;

if (!function_exists('social')) {
    function social() : SocialService
    {
        return app(SocialService::class);
    }
}
