<?php

namespace Flute\Admin\Http\Controllers;

use Flute\Core\Support\BaseController;
use Symfony\Component\HttpFoundation\Response;

class SidebarController extends BaseController
{
    public function getSidebar(): Response
    {
        cache()->delete('admin_menu_items');
        
        app(\Flute\Admin\AdminPackageFactory::class)->clearMenuCache();
        
        return response()->view('admin::layouts.sidebar');
    }
}

