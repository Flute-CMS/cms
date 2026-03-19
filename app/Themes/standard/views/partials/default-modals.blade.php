@if (config('auth.only_modal'))
    <x-modal id="auth-modal" title="{{ __('auth.header.login') }}" loadUrl="{{ '/login' }}" :inline="true">
        <x-slot:skeleton>
            <div class="modal-skeleton modal-skeleton--auth">
                {{-- Social buttons --}}
                <div class="modal-skeleton__socials">
                    <div class="skeleton modal-skeleton__social-btn"></div>
                </div>

                {{-- Divider --}}
                <div class="modal-skeleton__divider">
                    <span class="skeleton modal-skeleton__divider-text"></span>
                </div>

                {{-- Login/email field --}}
                <div class="modal-skeleton__field">
                    <div class="skeleton modal-skeleton__label"></div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Password field --}}
                <div class="modal-skeleton__field">
                    <div class="modal-skeleton__label-row">
                        <div class="skeleton modal-skeleton__label"></div>
                        <div class="skeleton modal-skeleton__label modal-skeleton__label--link"></div>
                    </div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Remember me --}}
                <div class="modal-skeleton__checkbox">
                    <div class="skeleton modal-skeleton__checkbox-box"></div>
                    <div class="skeleton modal-skeleton__checkbox-label"></div>
                </div>

                {{-- Submit --}}
                <div class="skeleton modal-skeleton__submit"></div>

                {{-- Footer --}}
                <div class="modal-skeleton__footer">
                    <div class="skeleton modal-skeleton__footer-text"></div>
                </div>
            </div>
        </x-slot:skeleton>
    </x-modal>

    <x-modal id="register-modal" title="{{ __('auth.header.register') }}" loadUrl="{{ '/register' }}" :inline="true">
        <x-slot:skeleton>
            <div class="modal-skeleton modal-skeleton--register">
                {{-- Social buttons --}}
                <div class="modal-skeleton__socials">
                    <div class="skeleton modal-skeleton__social-btn"></div>
                </div>

                {{-- Divider --}}
                <div class="modal-skeleton__divider">
                    <span class="skeleton modal-skeleton__divider-text"></span>
                </div>

                {{-- Name --}}
                <div class="modal-skeleton__field">
                    <div class="skeleton modal-skeleton__label"></div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Login --}}
                <div class="modal-skeleton__field">
                    <div class="skeleton modal-skeleton__label"></div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Email --}}
                <div class="modal-skeleton__field">
                    <div class="skeleton modal-skeleton__label"></div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Password --}}
                <div class="modal-skeleton__field">
                    <div class="skeleton modal-skeleton__label"></div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Password confirm --}}
                <div class="modal-skeleton__field">
                    <div class="skeleton modal-skeleton__label"></div>
                    <div class="skeleton modal-skeleton__input"></div>
                </div>

                {{-- Submit --}}
                <div class="skeleton modal-skeleton__submit"></div>

                {{-- Footer --}}
                <div class="modal-skeleton__footer">
                    <div class="skeleton modal-skeleton__footer-text"></div>
                </div>
            </div>
        </x-slot:skeleton>
    </x-modal>
@endif

@if (config('lk.only_modal'))
    <x-modal id="lk-modal" title="{{ __('lk.title') }}" loadUrl="{{ '/lk' }}" :inline="true">
        <x-slot:skeleton>
            <div class="modal-skeleton modal-skeleton--lk">
                {{-- Currency bar --}}
                <div class="modal-skeleton__currency-bar">
                    <div class="skeleton modal-skeleton__currency-item"></div>
                    <div class="skeleton modal-skeleton__currency-item"></div>
                    <div class="skeleton modal-skeleton__currency-item"></div>
                </div>

                {{-- Amount input --}}
                <div class="skeleton modal-skeleton__amount-input"></div>

                {{-- Presets --}}
                <div class="modal-skeleton__presets">
                    <div class="skeleton modal-skeleton__preset"></div>
                    <div class="skeleton modal-skeleton__preset"></div>
                    <div class="skeleton modal-skeleton__preset"></div>
                    <div class="skeleton modal-skeleton__preset"></div>
                </div>

                {{-- Gateway cards --}}
                <div class="modal-skeleton__gateways">
                    <div class="skeleton modal-skeleton__gateway-card"></div>
                    <div class="skeleton modal-skeleton__gateway-card"></div>
                </div>

                {{-- Promo --}}
                <div class="skeleton modal-skeleton__promo"></div>

                {{-- Submit --}}
                <div class="skeleton modal-skeleton__submit"></div>
            </div>
        </x-slot:skeleton>
    </x-modal>
@endif
