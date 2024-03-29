<?php

use Flute\Core\Page\PageManager;

if( !function_exists("page") )
{
    function page() : PageManager
    {
        return app(PageManager::class);
    }
}
