<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Composer\ComposerManager;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TablePreparation;

class ComposerController extends AbstractController
{
    protected $composerManager;

    public function __construct(ComposerManager $composerManager)
    {
        HasPermissionMiddleware::permission('admin.composer');
        $this->middleware(HasPermissionMiddleware::class);

        $this->composerManager = $composerManager;
    }

    public function table(FluteRequest $request)
    {
        $page = ($request->input("start", 1) + $request->input('length')) / $request->input('length');
        $draw = (int) $request->input("draw", 1);
        $search = $request->input("search", []);

        $length = (int) $request->input('length') > 100 ?: (int) $request->input('length');

        try {
            $data = $this->composerManager->getPackagistItems((int) $page, $search['value'], (int) $length);

            return $this->json([
                'draw' => $draw,
                'recordsTotal' => $data['total'],
                'recordsFiltered' => $data['total'],
                'data' => TablePreparation::normalize(
                    ['name', 'description', 'url', 'downloads', ''],
                    $data['results']
                )
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function install(FluteRequest $request)
    {
        $package = $request->package;
        $packages = $this->composerManager->getPackages();

        if (isset($packages[$package]))
            return $this->error(__('admin.compsoser.package_installed'));

        $this->composerManager->installPackage($request->package);

        user()->log('events.composer_install', $request->package);

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $package = $request->package;
        $packages = $this->composerManager->getPackages();

        if (!isset($packages[$package]))
            return $this->error(__('admin.compsoser.package_not_installed'));

        $this->composerManager->removePackage($request->package);

        user()->log('events.composer_deleted', $request->package);

        return $this->success();
    }
}