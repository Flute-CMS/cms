<?php

namespace Flute\Core\Http\Controllers\Topup;

use Flute\Core\Payments\Exceptions\PaymentPromoException;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class LKApiController extends AbstractController
{
    public function purchase(FluteRequest $request, string $gateway)
    {
        try {
            $this->throttle('lk_purchase');

            $redirect = payments()->processor()->purchase($gateway, $request->amount, (string) $request->promo, $request->currency);

            die($redirect);
        } catch (\Exception $e) {
            logs()->error($e);
            $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');

            if( config('lk.pay_in_new_window') ) {
                return $this->error($message);
            }

            return redirect('/lk')->withErrors($message);
        }
    }

    public function handle(FluteRequest $request, string $gateway): Response
    {
        try {
            payments()->processor()->handlePayment($gateway);

            user()->log('events.purchased', $gateway);

            return $this->success('1');
        } catch (\Exception $e) {
            logs()->warning($e);
            return $this->error('some error');
        }
    }

    public function validatePromo(FluteRequest $request): Response
    {
        $promo = $request->input('promo');

        try {
            $this->throttle('lk_validate_promo');

            $message = payments()->promo()->validate($promo);

            return $this->success($message);
        } catch (PaymentPromoException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            logs()->error($e);
            $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');
            return response()->error(500, $message);
        }
    }
}