<?php

use Flute\Core\Modules\Page\Services\PageManager;

if( !function_exists("page") )
{
    function page() : PageManager
    {
        return app(PageManager::class);
    }
}
