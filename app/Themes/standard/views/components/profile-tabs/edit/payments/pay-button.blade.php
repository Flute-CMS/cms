@if (!$invoice->isPaid)
    <x-button type="outline-primary" size="tiny" href="{{ url('/payment/' . $invoice->transactionId) }}"
        target="_blank" rel="noopener">{{ __('def.pay') }}</x-button>
@endif
