<div class="d-flex justify-content-end align-items-center gap-3">
    <x-button class="w-100" type="outline-primary" id="confirmation-dialog-cancel" autofocus data-a11y-dialog-hide>
        {{ __('def.cancel') }}
    </x-button>
    <x-button class="w-100" type="error" id="confirmation-dialog-confirm" hx-swap="morph">
        {{ __('def.confirm') }}
    </x-button>
</div>
