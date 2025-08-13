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
            if (!$request->isMethod('POST')) {
                return $this->error('Method not allowed', 405);
            }

            $this->throttle('payments.webhook');

            payments()->processor()->handlePayment($gateway);

            return $this->success('1');
        } catch (\Exception $e) {
            logs()->warning($e);

            if(is_debug()) {
                throw $e;
            }

            return $this->error('some error');
        }
    }
}
