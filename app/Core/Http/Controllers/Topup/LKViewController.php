<?php

namespace Flute\Core\Http\Controllers\Topup;

use Flute\Core\Support\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LKViewController extends AbstractController
{
    public function index(): Response
    {
        return view(tt('pages/lk/index'), [
            'payments' => payments()->getAllGateways()
        ]);
    }

    public function paymentFail(): Response
    {
        return view(tt('pages/lk/fail'));
    }

    public function paymentSuccess(): Response
    {
        return view(tt('pages/lk/success'));
    }
}