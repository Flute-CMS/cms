<?php

use Flute\Core\Services\FlashService;
use Flute\Core\Services\ToastService;
use Flute\Core\Toast\ToastBuilder;

if (!function_exists("flash")) {
    function flash(): FlashService
    {
        return app(FlashService::class);
    }
}

if (!function_exists("toast")) {
    function toast(): ToastBuilder
    {
        return app(ToastService::class)->toast();
    }
}
