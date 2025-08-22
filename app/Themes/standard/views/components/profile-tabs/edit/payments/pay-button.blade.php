<div>
    @if (!$invoice->isPaid)
        <x-button type="primary" size="tiny" href="{{ url('/payment/' . $invoice->transactionId) }}"
            target="_blank">{{ __('def.pay') ?? 'Pay' }}</x-button>
    @else
        -
    @endif
</div>
