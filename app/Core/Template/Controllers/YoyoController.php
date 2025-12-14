<?php

namespace Flute\Core\Template\Controllers;

use Exception;
use Flute\Core\Support\BaseController;
use Flute\Core\Template\Template;

class YoyoController extends BaseController
{
    public function handle(Template $template)
    {
        try {
            return response()->make($template->getYoyo()->update());
        } catch (Exception $e) {
            if (is_debug()) {
                throw $e;
            }

            logs()->error("Error in Yoyo update: ".$e->getMessage());

            return response()->error(500, $e->getMessage());
        }
    }
}
