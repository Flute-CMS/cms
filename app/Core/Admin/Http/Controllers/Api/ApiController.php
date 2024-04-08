<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class ApiController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.boss');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        $api = new ApiKey();

        $api->key = $request->input('key');

        transaction($api)->run();

        $this->syncPermissions($api, $request->input('permissions', []));

        user()->log('events.api_key_added', $request->input('key'));

        return $this->success();
    }

    public function delete(FluteRequest $request, $id)
    {
        $item = rep(ApiKey::class)->findByPK($id);

        transaction($item, 'delete')->run();
        
        user()->log('events.api_key_deleted', $request->input('key'));

        return $this->success();
    }

    private function syncPermissions(ApiKey $apiKey, array $permissions)
    {
        foreach ($permissions as $permissionId => $status) {
            $permission = rep(Permission::class)->findByPK($permissionId);

            $status = filter_var($status, FILTER_VALIDATE_BOOLEAN);

            if ($permission) {
                if ($status && !$apiKey->hasPermission($permission)) {
                    $apiKey->addPermission($permission);
                } elseif (!$status) {
                    $apiKey->removePermission($permission);
                }
            }
        }

        transaction($apiKey)->run();
    }
}