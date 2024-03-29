<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;

class HomeController extends AbstractController
{
    public function index() 
    {
        return view('pages/home.blade.php', [], true);
    }
}