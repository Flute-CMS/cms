<?php

namespace Flute\Core\Modules\Home\Controllers;

use Flute\Core\Support\BaseController;

class HomeController extends BaseController
{
    public function index()
    {
        return view('flute::pages.home');
    }
}
