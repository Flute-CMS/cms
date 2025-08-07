<?php

namespace Flute\Core\Modules\Search\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class SearchController extends BaseController
{
    public function search(FluteRequest $request, string $value)
    {
        return response()->json(
            app("search")->emit($value)
        );
    }
}
