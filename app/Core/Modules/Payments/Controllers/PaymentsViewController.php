<?php

namespace Flute\Core\Modules\Payments\Controllers;

use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class PaymentsViewController extends BaseController
{
    public function index(FluteRequest $fluteRequest)
    {
        return view('flute::pages.lk.index')
            ->fragmentIf($fluteRequest->isOnlyHtmx() && config('lk.only_modal'), 'lk-card');
    }

    public function processPayment($transaction)
    {
        $invoice = PaymentInvoice::findOne(['transactionId' => (string) $transaction]);

        if (!$invoice) {
            return $this->errors()->notFound("Invoice wasn't found");
        }

        if ($invoice->isPaid) {
            return $this->errors()->forbidden('Invoice already paid');
        }

        $gateway = PaymentGateway::findOne(['adapter' => $invoice->gateway]);

        if (!$gateway) {
            return $this->errors()->notFound("Adapter {$invoice->gateway} wasn't found");
        }

        try {
            payments()->processor()->processPayment($invoice, $gateway);
        } catch (\Exception $e) {
            return $this->errors()->badRequest($e->getMessage());
        }
    }

    public function paymentFail()
    {
        return view('flute::pages.lk.fail');
    }

    public function paymentSuccess()
    {
        return view('flute::pages.lk.success');
    }
}