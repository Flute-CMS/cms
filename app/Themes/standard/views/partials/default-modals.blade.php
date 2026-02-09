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
                <div class="lk-page" style="max-width: 680px; margin: 0 auto;">
                    <div style="background: var(--secondary); border: 1px solid var(--transp-1); border-radius: 20px; overflow: hidden;">
                        <div style="padding: var(--space-lg) var(--space-xl);">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <div class="skeleton" style="height: 40px; width: 120px; border-radius: 100px;"></div>
                                <div class="skeleton" style="height: 40px; width: 100px; border-radius: 100px;"></div>
                                <div class="skeleton" style="height: 40px; width: 130px; border-radius: 100px;"></div>
                            </div>
                        </div>
                        <div style="height: 1px; background: var(--transp-1); margin: 0 var(--space-xl);"></div>
                        <div style="padding: var(--space-lg) var(--space-xl);">
                            <div class="skeleton" style="height: 56px; border-radius: 14px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot:skeleton>
    </x-modal>
@endif
