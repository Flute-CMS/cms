<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.system');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function all()
    {
        $cachePath = BASE_PATH . '/storage/app/cache/*';
        $proxiesPath = BASE_PATH . '/storage/app/proxies/*';
        $viewsPath = BASE_PATH . '/storage/app/views/*';
        $translationsPath = BASE_PATH . '/storage/app/translations/*';
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $jsCachePath = BASE_PATH . '/public/assets/js/cache/*';

        $this->deleteFs($cachePath);
        $this->deleteFs($proxiesPath);
        $this->deleteFs($viewsPath);
        $this->deleteFs($translationsPath);
        $this->deleteFs($cssCachePath);
        $this->deleteFs($jsCachePath);

        return redirect(url('admin/settings', [
            'tab' => 'cache'
        ]));
    }

    public function template()
    {
        $viewsPath = BASE_PATH . '/storage/app/views/*';

        $this->deleteFs($viewsPath);

        return redirect(url('admin/settings', [
            'tab' => 'cache'
        ]));
    }

    public function translations()
    {
        $translationsPath = BASE_PATH . '/storage/app/translations/*';

        $this->deleteFs($translationsPath);

        return redirect(url('admin/settings', [
            'tab' => 'cache'
        ]));
    }

    public function styles()
    {
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $jsCachePath = BASE_PATH . '/public/assets/js/cache/*';

        $this->deleteFs($cssCachePath);
        $this->deleteFs($jsCachePath);

        return redirect(url('admin/settings', [
            'tab' => 'cache'
        ]));
    }

    protected function deleteFs($path)
    {
        $filesystem = new Filesystem();

        try {
            $filesystem->remove(glob($path));
        } catch (IOException $exception) {
            logs()->error($exception);
        }
    }
}
