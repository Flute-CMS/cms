<div class="profile-settings__section mb-4" id="two-factor-settings">
    <x-card>
        <x-slot:header>
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h4>{{ __('auth.two_factor.title') }}</h4>
                    <p class="text-muted mb-0">{{ __('auth.two_factor.description') }}</p>
                </div>
                @if ($isEnabled)
                    <x-badge type="success" icon="ph.regular.shield-check">
                        {{ __('profile.two_factor.status_enabled') }}
                    </x-badge>
                @else
                    <x-badge type="error" icon="ph.regular.shield">
                        {{ __('profile.two_factor.status_disabled') }}
                    </x-badge>
                @endif
            </div>
        </x-slot:header>

        @if ($showSetup && $tempSecret)
            <div class="two-factor-setup">
                <x-alert type="info" icon="ph.regular.info" :withClose="false" class="mb-4">
                    {{ __('auth.two_factor.setup_description') }}
                </x-alert>

                <div class="row gx-4 gy-4">
                    <div class="col-md-5">
                        <div class="two-factor-qr">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUri) }}" 
                                 alt="QR Code" />
                        </div>
                    </div>

                    <div class="col-md-7">
                        <x-forms.field class="mb-3">
                            <x-forms.label>{{ __('auth.two_factor.secret_key') }}:</x-forms.label>
                            <div class="input-group">
                                <code class="two-factor-secret">{{ $tempSecret }}</code>
                                <x-button type="outline-primary" size="tiny" data-copy="{{ $tempSecret }}" data-tooltip="{{ __('def.copy') }}" title="{{ __('def.copy') }}">
                                    <x-icon path="ph.regular.copy" />
                                </x-button>
                            </div>
                        </x-forms.field>

                        <x-forms.field>
                            <x-forms.label for="verificationCode" required>
                                {{ __('auth.two_factor.verify_code') }}:
                            </x-forms.label>
                            <x-fields.otp-input 
                                name="verificationCode" 
                                :length="6"
                                value="{{ $verificationCode ?? '' }}" />
                        </x-forms.field>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer gap-2">
                    <x-button type="outline-primary" size="small" yoyo:post="cancelSetup" yoyo:on="click">
                        {{ __('def.cancel') }}
                    </x-button>
                    <x-button type="primary" size="small" withLoading yoyo:post="confirmSetup" yoyo:on="click">
                        {{ __('auth.two_factor.verify') }}
                    </x-button>
                </div>
            </x-slot:footer>

        @elseif ($showRecoveryCodes && $tempRecoveryCodes)
            <div class="two-factor-recovery">
                <x-alert type="warning" icon="ph.regular.warning" :withClose="false" class="mb-4">
                    {{ __('auth.two_factor.recovery_codes_warning') }}
                </x-alert>

                <div class="two-factor-codes">
                    @foreach ($tempRecoveryCodes as $code)
                        <code>{{ $code }}</code>
                    @endforeach
                </div>

                <div class="d-flex gap-2 mt-4">
                    <x-button type="outline-primary" size="tiny" data-copy="{{ implode('\n', $tempRecoveryCodes) }}" data-tooltip="{{ __('def.copy') }}" title="{{ __('def.copy') }}">
                        <x-icon path="ph.regular.copy" />
                        {{ __('auth.two_factor.copy_codes') }}
                    </x-button>
                    <x-button type="outline-primary" size="tiny" onclick="downloadRecoveryCodes()">
                        <x-icon path="ph.regular.download-simple" />
                        {{ __('auth.two_factor.download_codes') }}
                    </x-button>
                </div>

                <script>
                    function downloadRecoveryCodes() {
                        const codes = @json($tempRecoveryCodes);
                        const blob = new Blob([codes.join('\n')], { type: 'text/plain' });
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = '2fa-recovery-codes.txt';
                        a.click();
                    }
                </script>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="primary" size="small" yoyo:post="closeRecoveryCodes" yoyo:on="click">
                        {{ __('def.done') }}
                    </x-button>
                </div>
            </x-slot:footer>

        @elseif ($isEnabled)
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <x-icon path="ph.regular.shield-check" class="text-success" style="font-size: var(--h3);" />
                    <div>
                        <p class="mb-0">{{ __('auth.two_factor.enabled') }}</p>
                        @if ($user->two_factor_confirmed_at)
                            <small class="text-muted">
                                {{ __('profile.two_factor.last_enabled', ['date' => carbon($user->two_factor_confirmed_at)->diffForHumans()]) }}
                            </small>
                        @endif
                    </div>
                </div>
                <x-button type="outline-primary" size="tiny" withLoading yoyo:post="regenerateRecoveryCodes" yoyo:on="click">
                    <x-icon path="ph.regular.arrows-clockwise" />
                    {{ __('auth.two_factor.regenerate_codes') }}
                </x-button>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="error" size="small" withLoading yoyo:post="disable" yoyo:on="click">
                        {{ __('auth.two_factor.disable') }}
                    </x-button>
                </div>
            </x-slot:footer>

        @else
            <div class="d-flex align-items-center gap-3">
                <x-icon path="ph.regular.shield" class="text-muted" style="font-size: var(--h3);" />
                <p class="mb-0 text-muted">{{ __('auth.two_factor.disabled') }}</p>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="primary" size="small" withLoading yoyo:post="startSetup" yoyo:on="click">
                        <x-icon path="ph.regular.shield-plus" />
                        {{ __('auth.two_factor.enable') }}
                    </x-button>
                </div>
            </x-slot:footer>
        @endif
    </x-card>
</div>
