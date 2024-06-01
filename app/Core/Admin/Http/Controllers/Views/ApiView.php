<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Nette\Utils\Random;

class ApiView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.boss');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(FluteRequest $request)
    {
        $apikeys = rep(ApiKey::class)->findAll();

        $table = table();

        $table->setPhrases([
            'key' => __('admin.api.key'),
        ]);

        foreach ($apikeys as $api) {
            $api->key = '<div hidden>' . $api->key . '</div>';
        }

        $table->fromEntity($apikeys, ['permissions'])->withDelete('api');

        $table->updateColumn('key', [
            'clean' => false,
        ]);

        return view("Core/Admin/Http/Views/pages/api/index", [
            'table' => $table->render()
        ]);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/api/add", [
            "permissions" => rep(Permission::class)->findAll(),
            "random" => Random::generate(30)
        ]);
    }
}