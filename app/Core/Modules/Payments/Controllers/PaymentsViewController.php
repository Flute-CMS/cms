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

        return view('flute::pages.lk.index', [
            'isModal' => $isModal,
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
            return payments()->processor()->processPayment($invoice, $gateway);
        } catch (Throwable $e) {
            return $this->errors()->badRequest($e->getMessage());
        }
    }

    public function paymentFail()
    {
        return view('flute::pages.lk.fail');
    }

    public function paymentSuccess(FluteRequest $request)
    {
        if ($request->isMethod('POST')) {
            $this->tryHandlePostbackFromSuccess($request);
        }

        return view('flute::pages.lk.success');
    }

    /**
     * Some gateways post callback data to returnUrl (/lk/success) instead of notifyUrl.
     * In this case we try to resolve invoice and run standard webhook handler.
     */
    private function tryHandlePostbackFromSuccess(FluteRequest $request): void
    {
        $payload = (array) $request->input();

        $statusKeys = ['Status', 'status', 'payment_status', 'paymentStatus'];
        $hasStatus = false;
        foreach ($statusKeys as $statusKey) {
            if (array_key_exists($statusKey, $payload)) {
                $hasStatus = true;

                break;
            }
        }

        if (!$hasStatus) {
            return;
        }

        $transactionId = $this->extractTransactionIdFromPayload($payload);

        if ($transactionId === '') {
            logs()->debug('payments.success.postback.transaction_not_found', [
                'keys' => array_keys($payload),
            ]);

            return;
        }

        $invoice = PaymentInvoice::findOne(['transactionId' => $transactionId]);
        if (!$invoice) {
            logs()->warning('payments.success.postback.invoice_not_found', [
                'transaction_id' => $transactionId,
            ]);

            return;
        }

        if ($invoice->isPaid) {
            return;
        }

        try {
            payments()->processor()->handlePayment($invoice->gateway);
        } catch (Throwable $e) {
            if (stripos($e->getMessage(), 'already paid') !== false) {
                return;
            }

            logs()->warning('payments.success.postback.handle_failed', [
                'transaction_id' => $transactionId,
                'gateway' => $invoice->gateway,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract internal transaction id from generic payment callback payload.
     */
    private function extractTransactionIdFromPayload(array $payload): string
    {
        $candidateKeys = [
            'transactionId',
            'transaction_id',
            'transactionReference',
            'transaction_reference',
            'order_id',
            'orderId',
            'merchant_order_id',
            'merchantOrderId',
            'invoice',
            'invoice_id',
            'invoiceId',
            'inv_id',
            'payment_id',
            'paymentId',
        ];

        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $payload) || !is_scalar($payload[$key])) {
                continue;
            }

            $candidate = trim((string) $payload[$key]);
            if ($candidate === '') {
                continue;
            }

            if (PaymentInvoice::findOne(['transactionId' => $candidate])) {
                return $candidate;
            }

            if (preg_match('/\d{8,}/', $candidate, $matches)) {
                $digitsCandidate = $matches[0];
                if (PaymentInvoice::findOne(['transactionId' => $digitsCandidate])) {
                    return $digitsCandidate;
                }
            }
        }

        $lookupCount = 0;
        foreach ($payload as $value) {
            $resolved = $this->extractTransactionIdFromAnyValue($value, $lookupCount);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        return '';
    }

    /**
     * Best-effort recursive extraction for unknown gateway payload shapes.
     * Limited to 20 DB lookups to prevent DoS.
     */
    private function extractTransactionIdFromAnyValue($value, int &$lookupCount = 0): string
    {
        $maxLookups = 20;

        if ($lookupCount >= $maxLookups) {
            return '';
        }

        if (is_array($value)) {
            foreach ($value as $nested) {
                $resolved = $this->extractTransactionIdFromAnyValue($nested, $lookupCount);
                if ($resolved !== '') {
                    return $resolved;
                }
            }

            return '';
        }

        if (!is_scalar($value)) {
            return '';
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (++$lookupCount > $maxLookups) {
            return '';
        }

        if (PaymentInvoice::findOne(['transactionId' => $text])) {
            return $text;
        }

        if (preg_match_all('/\d{8,}/', $text, $matches)) {
            foreach ($matches[0] as $digitsCandidate) {
                if (++$lookupCount > $maxLookups) {
                    return '';
                }

                if (PaymentInvoice::findOne(['transactionId' => $digitsCandidate])) {
                    return $digitsCandidate;
                }
            }
        }

        return '';
    }
}
