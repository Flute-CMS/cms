@if (config('auth.only_modal'))
    <x-modal id="auth-modal" title="{{ __('auth.header.login') }}" loadUrl="{{ '/login' }}" :inline="true">
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

    <x-modal id="register-modal" title="{{ __('auth.header.register') }}" loadUrl="{{ '/register' }}" :inline="true">
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
    <x-modal id="lk-modal" title="{{ __('lk.title') }}" loadUrl="{{ '/lk' }}" :inline="true">
        <x-slot:skeleton>
            <div class="modal__content-loading">
                <div class="lk-modal-wrap" style="max-width: 480px; margin: 0 auto;">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="skeleton" style="height: 38px; flex: 1; border-radius: var(--border05);"></div>
                        <div class="skeleton" style="height: 38px; flex: 1; border-radius: var(--border05);"></div>
                        <div class="skeleton" style="height: 38px; flex: 1; border-radius: var(--border05);"></div>
                        <div class="skeleton" style="height: 38px; flex: 1; border-radius: var(--border05);"></div>
                    </div>
                    <div class="skeleton mb-3" style="height: 42px; border-radius: var(--border05);"></div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="skeleton" style="height: 42px; width: 120px; border-radius: 7px;"></div>
                        <div class="skeleton" style="height: 42px; width: 100px; border-radius: 7px;"></div>
                    </div>
                    <div class="skeleton" style="height: 44px; border-radius: var(--border05);"></div>
                </div>
            </div>
        </x-slot:skeleton>
    </x-modal>
@endif
