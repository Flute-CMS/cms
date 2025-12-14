<?php

namespace Flute\Core\Modules\Translation\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class TranslationController extends BaseController
{
    public function translate(FluteRequest $request)
    {
        $translations = $request->input('translations');

        if (!$translations || !is_array($translations)) {
            return $this->json(['error' => 'Translations is required'], 422);
        }

        $result = [];

        foreach ($translations as $key => $value) {
            $phraseKey = is_array($value) ? ($value['phrase'] ?? null) : null;
            if (!is_string($phraseKey) || $phraseKey === '') {
                continue;
            }

            $replace = [];
            if (isset($value['replace']) && is_array($value['replace'])) {
                foreach ($value['replace'] as $rk => $rv) {
                    if (is_scalar($rv)) {
                        $replace[$rk] = (string) $rv;
                    }
                }
            }

            $locale = isset($value['locale']) && is_string($value['locale']) ? $value['locale'] : app()->getLang();

            $phrase = __($phraseKey, $replace, $locale);

            $result[] = [
                'key' => $phraseKey,
                'result' => $phrase,
            ];
        }

        return $this->json($result);
    }
}
