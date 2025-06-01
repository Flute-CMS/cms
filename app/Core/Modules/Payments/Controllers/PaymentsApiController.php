<?php

namespace Flute\Core\Modules\Payments\Controllers;

use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class PaymentsApiController extends BaseController
{
    public function handle(FluteRequest $request, string $gateway): Response
    {
        try {
            payments()->processor()->handlePayment($gateway);

            return $this->success('1');
        } catch (\Exception $e) {
            logs()->warning($e);
            return $this->error('some error');
        }
    }
}