<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class TranslationController extends AbstractController
{
    public function translate( FluteRequest $request ) 
    {
        $translations = $request->input('translations');

        if( !$translations )
            return $this->error('Translations is required');

        $result = [];

        foreach( $translations as $key => $value )
        {
            $result[] = [
                'key' => $value['phrase'],
                'result' => __(
                    $value['phrase'],
                    $value['replace'] ?? [],
                    $value['locale'] ?? app()->getLang()
                )
            ];
        }

        return $this->json($result);
    }
}