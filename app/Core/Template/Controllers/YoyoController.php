<?php

namespace Flute\Core\Template\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Template\Template;

class YoyoController extends BaseController
{
    public function handle(Template $template)
    {
        try {
            $this->normalizeYoyoRequest();

            return response()->make($template->getYoyo()->update());
        } catch (Throwable $e) {
            if (is_debug()) {
                throw $e;
            }

            logs()->error('Error in Yoyo update: ' . $e->getMessage());

            return response()->error(500, __('def.unknown_error'));
        }
    }

    private function normalizeYoyoRequest(): void
    {
        $this->normalizeJsonParam('actionArgs');
        $this->normalizeJsonParam('eventParams');
    }

    private function normalizeJsonParam(string $key): void
    {
        $value = request()->get($key);

        if (!is_string($value)) {
            return;
        }

        $value = trim($value);

        if ($value === '') {
            request()->request->set($key, []);

            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        if (!is_array($decoded)) {
            $decoded = $decoded === null ? [] : [$decoded];
        }

        request()->request->set($key, $decoded);
    }
}
