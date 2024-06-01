<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Support\AdminSearchHandler;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class AdminSearchController extends AbstractController
{
    public function search(FluteRequest $request, string $value)
    {
        return response()->json(
            app(AdminSearchHandler::class)->emit($value)
        );
    }
}