<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\PhosphorIcons\PhosphorIconsParser;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class IconsController extends AbstractController
{
    public function getAll(FluteRequest $request)
    {
        return $this->json([
            'icons' => app(PhosphorIconsParser::class)->getAll()
        ]);
    }
}