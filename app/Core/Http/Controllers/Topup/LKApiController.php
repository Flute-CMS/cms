<?php

namespace Flute\Core\Http\Controllers\Topup;

use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Payments\Exceptions\PaymentPromoException;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class LKApiController extends AbstractController
{
    public function __construct()
    {
    }

    public function purchase(FluteRequest $request, string $gateway): Response
    {
        try {
            $payment = payments()->processor()->purchase($gateway, $request->amount, $request->promo);

            return $this->json([
                'link' => $payment
            ]);
        } catch (\Exception $e) {
            logs()->error($e);
            // return $this->error($e->getMessage());
            return $this->error(__('def.unknown_error'));
        }
    }

    public function handle(FluteRequest $request, string $gateway): Response
    {
        try {
            payments()->processor()->handlePayment($gateway);
            return redirect(url('/lk/success'));
        } catch (\Exception $e) {
            logs()->warning($e);
            return redirect(url('/lk/fail'));
        }
    }

    public function validatePromo(FluteRequest $request): Response
    {
        $promo = $request->input('promo');

        try {
            $message = payments()->promo()->validate($promo);

            return $this->success($message);
        } catch (PaymentPromoException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            logs()->error($e);

            return $this->error(__('def.unknown_error'));
            // return $this->error($e->getMessage());
        }
    }
}