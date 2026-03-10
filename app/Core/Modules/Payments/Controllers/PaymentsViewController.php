<?php

namespace Flute\Core\Modules\Payments\Controllers;

use Exception;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class PaymentsViewController extends BaseController
{
    public function index(FluteRequest $fluteRequest)
    {
        $isModal = $fluteRequest->isOnlyHtmx() && config('lk.only_modal');

        $recentInvoices = [];
        $gatewayNames = [];
        if (!$isModal) {
            $recentInvoices = PaymentInvoice::query()
                ->where('user_id', user()->id)
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->fetchAll();

            $adapters = array_unique(array_map(fn ($i) => $i->gateway, $recentInvoices));
            if ($adapters) {
                foreach (PaymentGateway::query()->where('adapter', 'IN', $adapters)->fetchAll() as $gw) {
                    $gatewayNames[$gw->adapter] = $gw->name;
                }
            }
        }

        return view('flute::pages.lk.index', [
            'isModal' => $isModal,
            'recentInvoices' => $recentInvoices,
            'gatewayNames' => $gatewayNames,
        ])->fragmentIf($isModal, 'lk-card');
    }

    public function processPayment($transaction)
    {
        $invoice = PaymentInvoice::findOne(['transactionId' => (string) $transaction]);

        if (!$invoice) {
            return $this->errors()->notFound("Invoice wasn't found");
        }

        if ($invoice->user->id !== user()->id) {
            return $this->errors()->forbidden('Access denied');
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
        } catch (Exception $e) {
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
