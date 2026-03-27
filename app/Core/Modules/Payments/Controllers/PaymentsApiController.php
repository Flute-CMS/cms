<?php

namespace Flute\Core\Modules\Payments\Controllers;

use Exception;
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

            logs()->debug('payments.webhook.incoming', [
                'gateway' => $gateway,
                'keys' => array_keys((array) $request->input()),
            ]);

            payments()->processor()->handlePayment($gateway);

            logs()->info('payments.webhook.processed', [
                'gateway' => $gateway,
            ]);

            return $this->success('OK');
        } catch (Throwable $e) {
            logs()->warning('payments.webhook.failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'keys' => array_keys((array) $request->input()),
            ]);

            if (is_debug()) {
                throw $e;
            }

            return $this->error('some error');
        }
    }
}
