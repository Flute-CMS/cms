<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Admin\Services\DatabasesService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class DatabasesController extends AbstractController
{
    protected DatabasesService $databasesService;    

    public function __construct(DatabasesService $databasesService)
    {
        $this->databasesService = $databasesService;
        HasPermissionMiddleware::permission('admin.system');
    }

    public function store(FluteRequest $request): Response
    {
        try {
            $this->validate($request);

            $this->databasesService->store(
                $request->mod,
                $request->dbname,
                $request->additional ?? '{}',
                (int) $request->sid
            );

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(FluteRequest $request, $id): Response
    {
        try {
            $this->databasesService->delete((int) $id);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(FluteRequest $request, $id): Response
    {
        try {
            $this->validate($request);

            $this->databasesService->update(
                (int) $id,
                $request->mod,
                $request->dbname,
                $request->additional ?? '{}',
                (int) $request->sid
            );

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function validate( FluteRequest $request )
    {
        if( empty( $request->input('mod') ) || empty( $request->input('dbname') ) )
            throw new \Exception(__('admin.databases.params_empty'));
    }
}