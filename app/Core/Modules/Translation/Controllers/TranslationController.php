<?php

namespace Flute\Core\Modules\Translation\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class TranslationController extends BaseController
{
    public function translate(FluteRequest $request)
    {
        $translations = $request->input('translations');

        if (!$translations) {
            return $this->error('Translations is required');
        }

        $result = [];

        foreach ($translations as $key => $value) {
            $phrase = __(
                $value['phrase'],
                $value['replace'] ?? [],
                $value['locale'] ?? app()->getLang()
            );

            $result[] = [
                'key' => $value['phrase'],
                'result' => $phrase,
            ];
        }

        return $this->json($result);
    }
}
