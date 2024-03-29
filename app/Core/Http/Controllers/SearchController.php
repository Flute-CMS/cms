<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class SearchController extends AbstractController
{
    public function search( FluteRequest $request, string $value ) 
    {
        return response()->json(
            app("search")->emit( $value )
        );
    }
}