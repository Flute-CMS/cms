<?php

use Flute\Core\Services\UserNameRenderer;

if (!function_exists('user_name_renderer')) {
    function user_name_renderer(): UserNameRenderer
    {
        return app(UserNameRenderer::class);
    }
}
