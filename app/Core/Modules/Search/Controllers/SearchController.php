<?php

namespace Flute\Core\Modules\Search\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class SearchController extends BaseController
{
    public function search(FluteRequest $request, string $value)
    {
        $value = trim($value);
        
        if ($value === '' || mb_strlen($value) > 128) {
            return $this->json(['error' => 'Invalid query'], 422);
        }
        
        return response()->json(
            app("search")->emit($value)
        );
    }
}
