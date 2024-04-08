<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Composer\Console\Application;
use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TablePreparation;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ComposerController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.composer');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function table(FluteRequest $request)
    {
        $page = ($request->input("start", 1) + $request->input('length')) / $request->input('length');
        $draw = (int) $request->input("draw", 1);
        $search = $request->input("search", []);

        $length = (int) $request->input('length') > 100 ?: (int) $request->input('length');

        try {
            $data = $this->getPackagistItems((int) $page, $search['value'], (int) $length);

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

    protected function getPackagistItems(?int $page, ?string $search, ?int $length)
    {
        // return cache()->callback('flute.composer.packagist', function () {
        set_time_limit(0);
        $guzzle = new Client;

        if (!empty($search)) {
            $res = $guzzle->get("https://packagist.org/search.json?q=$search&per_page=$length&page=$page");
            return json_decode($res->getBody()->getContents(), true);
        } else {
            $res = $guzzle->get("https://packagist.org/explore/popular.json?per_page=$length&page=$page");
            $content = json_decode($res->getBody()->getContents(), true);

            return [
                'results' => $content['packages'],
                'total' => $content['total']
            ];
        }
        // });
    }

    public function install(FluteRequest $request)
    {
        $package = $request->package;
        $packages = $this->getPackages();

        if (isset($packages[$package]))
            return $this->error(__('admin.compsoser.package_installed'));

        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            array(
                'command' => "require",
                'packages' => [
                    $request->package
                ],
                '--working-dir' => BASE_PATH,
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true
            )
        );

        $output = new BufferedOutput();
        $app->run($input, $output);

        user()->log('events.composer_install', $request->package);

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $package = $request->package;
        $packages = $this->getPackages();

        if (!isset($packages[$package]))
            return $this->error(__('admin.compsoser.package_not_installed'));

        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            array(
                'command' => "remove",
                'packages' => [
                    $request->package
                ],
                '--working-dir' => BASE_PATH,
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true
            )
        );

        $output = new BufferedOutput();
        $app->run($input, $output);

        user()->log('events.composer_deleted', $request->package);

        return $this->success();
    }

    protected function getPackages()
    {
        $packages = json_decode(file_get_contents(BASE_PATH . 'composer.json'), true);

        return $packages['require'];
    }
}