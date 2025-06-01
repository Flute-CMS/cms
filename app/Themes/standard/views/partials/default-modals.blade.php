@push('footer')
    @if (config('auth.only_modal'))
        <x-modal id="auth-modal" title="{{ __('auth.header.login') }}" loadUrl="{{ '/login' }}">
            <x-slot:skeleton>
                <div class="modal__content-loading">
                    <div class="d-flex mb-4 flex-wrap gap-2">
                        <div class="skeleton w-100" style="height: 40px; border-radius: var(--border05);"></div>
                    </div>

                    <div class="skeleton mb-3" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton mb-3" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton mb-4" style="height: 30px; width: 50%; border-radius: var(--border05);"></div>
                    <div class="skeleton" style="height: 48px; border-radius: var(--border05);"></div>
                </div>
            </x-slot:skeleton>
        </x-modal>

        <x-modal id="register-modal" title="{{ __('auth.header.register') }}" loadUrl="{{ '/register' }}">
            <x-slot:skeleton>
                <div class="modal__content-loading">
                    <div class="d-flex mb-4 flex-wrap gap-2">
                        <div class="skeleton w-100" style="height: 40px; border-radius: var(--border05);"></div>
                    </div>

                    <div class="skeleton mb-3" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton mb-3" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton mb-3" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton mb-3" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton mb-4" style="height: 70px; border-radius: var(--border05);"></div>
                    <div class="skeleton" style="height: 48px; border-radius: var(--border05);"></div>
                </div>
            </x-slot:skeleton>
        </x-modal>
    @endif

    @if (config('lk.only_modal'))
        <x-modal id="lk-modal" title="{{ __('lk.title') }}" loadUrl="{{ '/lk' }}">
            <x-slot:skeleton>
                <div class="modal__content-loading">
                    <div class="lk-payment-layout">
                        <div class="lk-payment-main">
                            <div class="skeleton mb-4" style="height: 40px; width: 60%; border-radius: var(--border05);">
                            </div>
                            <div class="d-flex mb-4 flex-wrap gap-2">
                                <div class="skeleton"
                                    style="height: 60px; width: calc(33.333% - 0.5rem); min-width: 100px; border-radius: var(--border05);">
                                </div>
                                <div class="skeleton"
                                    style="height: 60px; width: calc(33.333% - 0.5rem); min-width: 100px; border-radius: var(--border05);">
                                </div>
                                <div class="skeleton"
                                    style="height: 60px; width: calc(33.333% - 0.5rem); min-width: 100px; border-radius: var(--border05);">
                                </div>
                            </div>
                        </div>

                        <div class="skeleton lk-payment-summary-skeleton"
                            style="height: 250px; border-radius: var(--border1);"></div>
                    </div>
                </div>
            </x-slot:skeleton>
        </x-modal>
    @endif
@endpush
