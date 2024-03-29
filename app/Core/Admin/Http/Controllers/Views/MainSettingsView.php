<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class MainSettingsView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.system');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function index(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/main/index");
    }
}