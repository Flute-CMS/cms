<?php

use Illuminate\Support\Facades\Blade;
use Tests\Stubs\EmptyIconComponent;

if (!function_exists('transaction')) {
    function transaction($entity)
    {
        return new class {
            public function run()
            { /* no-op */
            }
        };
    }
}

// Remove tracy debug bar
if (!function_exists('is_debug')) {
    function is_debug()
    {
        return false;
    }
}

require_once __DIR__ . '/../bootstrap/app.php';

if (function_exists('app')) {
    Blade::component('icon', EmptyIconComponent::class);
}