@push('modals')
<div class="modal dialog-container" id="confirmation-dialog"
    role="dialog" aria-hidden="true" aria-labelledby="confirmation-dialog-title" aria-describedby="confirmation-dialog-content"
    data-a11y-dialog>

    <div class="modal__overlay dialog-overlay" tabindex="-1"></div>

    <div class="modal__container dialog-content" role="document" tabindex="0">
        <header class="modal__header">
            <h4 class="modal__title" id="confirmation-dialog-title">{{ __('def.are_you_sure') }}</h4>
            <button class="modal__close dialog-close" aria-label="Close modal" data-tooltip="{{ __('def.close') }}"
                data-a11y-dialog-hide="confirmation-dialog"></button>
        </header>

        <div class="modal__content dialog-body" id="confirmation-dialog-content">
            <div class="confirmation-dialog__icon" id="confirmation-dialog-icon">
                @php
                    $iconFinder = app(\Flute\Core\Modules\Icons\Services\IconFinder::class);
                @endphp
                <span class="icon-error" style="display: none;">{!! $iconFinder->loadFile('ph.regular.x-circle') !!}</span>
                <span class="icon-success" style="display: none;">{!! $iconFinder->loadFile('ph.regular.check-circle') !!}</span>
                <span class="icon-info" style="display: none;">{!! $iconFinder->loadFile('ph.regular.info') !!}</span>
                <span class="icon-warning" style="display: none;">{!! $iconFinder->loadFile('ph.regular.warning-circle') !!}</span>
            </div>

            <div id="confirmation-dialog-message" class="text-center">
            </div>
        </div>

        <footer class="modal__footer">
            <div class="d-flex justify-content-end align-items-center gap-3 w-100" hx-swap="morph">
                <button class="btn btn-outline-primary btn-medium w-100" type="button" id="confirmation-dialog-cancel" autofocus
                    data-a11y-dialog-hide>
                    {{ __('def.cancel') }}
                </button>
                <button class="btn btn-error btn-medium w-100" type="button" id="confirmation-dialog-confirm">
                    {{ __('def.confirm') }}
                </button>
            </div>
        </footer>
    </div>
</div>
@endpush
