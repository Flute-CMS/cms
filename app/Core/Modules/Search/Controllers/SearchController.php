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

        $cacheKey = 'api.search.results.' . sha1(mb_strtolower($value));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return response()->json($cached);
        }

        $results = app("search")->emit($value);

        cache()->set($cacheKey, $results, 30);

        return response()->json($results);
    }
}
