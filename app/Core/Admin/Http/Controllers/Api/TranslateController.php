<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Nette\Utils\FileSystem;

class TranslateController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.translate');
        $this->middleware(HasPermissionMiddleware::class);

        // $this->middleware(CSRFMiddleware::class);
    }
    public function edit(FluteRequest $request)
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $key => $val) {
            if (!in_array($key, config('lang.available')))
                continue;

            $path = BASE_PATH . "i18n/{$key}/custom.php";
            $values = [];

            if (!file_exists($path))
                file_put_contents($path, '<?php return [];');

            foreach ($val as $item) {
                $values[$item['key']] = $item['value'];
            }

            fs()->updateConfig($path, $values);
        }

        return $this->success();
    }
}