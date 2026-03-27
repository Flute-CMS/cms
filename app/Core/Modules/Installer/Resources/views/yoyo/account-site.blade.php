<div class="account-site-step">
    <div class="step-panel">
        <div class="step-header">
            <div class="step-header__icon step-header__icon--purple">
                <x-icon path="ph.regular.user-circle-gear" />
            </div>
            <h1>{{ __('install.account_site.heading') }}</h1>
            <p class="step-subtitle">{{ __('install.account_site.subtitle') }}</p>
        </div>

        <div class="step-body">
            <form hx-post="{{ route('installer.step3.save') }}" hx-target="body" hx-swap="morph">

                <!-- Admin Section -->
                <div class="setup-section">
                    <div class="setup-section__header">
                        <div class="setup-section__icon">
                            <x-icon path="ph.regular.user-circle-gear" />
                        </div>
                        <div class="setup-section__title">{{ __('install.account_site.admin_section') }}</div>
                    </div>

                    <div class="field">
                        <label class="field__label">{{ __('install.account_site.name') }} <span class="required">*</span></label>
                        <input type="text" name="name" class="field__input" value="{{ $name }}" required />
                        @error('name') <span class="field__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="name-row">
                        <div class="field">
                            <label class="field__label">{{ __('install.account_site.email') }} <span class="required">*</span></label>
                            <input type="email" name="email" class="field__input" value="{{ $email }}" required />
                            @error('email') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label class="field__label">
                                {{ __('install.account_site.login') }} <span class="required">*</span>
                                <span data-tooltip="{{ __('install.account_site.login_help') }}" data-tooltip-placement="top" class="tooltip-trigger">?</span>
                            </label>
                            <input type="text" name="login" class="field__input" value="{{ $login }}" required pattern="[A-Za-z0-9\-_\.]{6,20}" />
                            @error('login') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="name-row">
                        <div class="field">
                            <label class="field__label">{{ __('install.account_site.password') }} <span class="required">*</span></label>
                            <input type="password" name="password" class="field__input" placeholder="{{ __('install.account_site.password_placeholder') }}" required />
                            @error('password') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label class="field__label">{{ __('install.account_site.password_confirmation') }} <span class="required">*</span></label>
                            <input type="password" name="password_confirmation" class="field__input" placeholder="{{ __('install.account_site.password_confirm_placeholder') }}" required />
                        </div>
                    </div>
                </div>

                <hr class="divider" />

                <!-- Site Section -->
                <div class="setup-section">
                    <div class="setup-section__header">
                        <div class="setup-section__icon">
                            <x-icon path="ph.regular.globe" />
                        </div>
                        <div class="setup-section__title">{{ __('install.account_site.site_section') }}</div>
                    </div>

                    <div class="name-row">
                        <div class="field">
                            <label class="field__label">{{ __('install.account_site.site_name') }} <span class="required">*</span></label>
                            <input type="text" name="siteName" class="field__input" value="{{ $siteName }}" required />
                            @error('siteName') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label class="field__label">{{ __('install.account_site.site_url') }} <span class="required">*</span></label>
                            <input type="url" name="siteUrl" class="field__input field__input--mono" value="{{ $siteUrl }}" required />
                            @error('siteUrl') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label">{{ __('install.account_site.site_description') }}</label>
                        <input type="text" name="siteDescription" class="field__input" value="{{ $siteDescription }}" placeholder="{{ __('install.account_site.site_description_placeholder') }}" />
                    </div>

                    <div class="field">
                        <label class="field__label">{{ __('install.account_site.timezone') }} <span class="required">*</span></label>
                        <x-select name="timezone" :options="$timezones" :selected="$timezone" :searchable="true" />
                        @error('timezone') <span class="field__error">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($errorMessage)
                    <div class="alert alert--danger" style="margin-top: 16px;">
                        {{ $errorMessage }}
                    </div>
                @endif

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <a href="{{ route('installer.step', ['id' => 2]) }}" class="btn btn--link" hx-boost="true">
                            <span class="btn__label">
                                <x-icon path="ph.regular.caret-left" />
                                {{ __('install.common.back') }}
                            </span>
                        </a>
                        <button type="submit" class="btn btn--primary">
                            <span class="btn__spinner"></span>
                            <span class="btn__label">
                                {{ __('install.common.next') }}
                                <x-icon path="ph.regular.arrow-right" />
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
