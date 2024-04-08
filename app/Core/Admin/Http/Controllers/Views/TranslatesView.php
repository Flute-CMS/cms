<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class TranslatesView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.translate');
    }

    public function list(): Response
    {
        $translations = $this->getTranslations();

        return view("Core/Admin/Http/Views/pages/translates/list", [
            'translations' => $translations
        ]);
    }

    public function editTranslate(FluteRequest $fluteRequest, string $code)
    {
        if (!in_array($code, config('lang.all')))
            return $this->error(__('admin.translate.unknown_lang'), 404);

        return view("Core/Admin/Http/Views/pages/translates/edit", [
            'translations' => $this->getTranslationFile($code),
            'code' => $code
        ]);
    }

    protected function getTranslations(): array
    {
        $languages = config('lang.available');
        $translations = [];

        foreach ($languages as $code) {
            $translations[$code] = $this->getTranslationFile($code);
        }

        return $translations;
    }

    protected function getTranslationFile(string $code)
    {
        $filePath = BASE_PATH . "/i18n/{$code}/custom.php";

        if (file_exists($filePath)) {
            return require $filePath;
        } else {
            return [];
        }
    }
}