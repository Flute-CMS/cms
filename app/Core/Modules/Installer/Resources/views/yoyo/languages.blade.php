<div class="languages-step">
    <div class="step-panel">
        <div class="step-header">
            <div class="step-header__icon step-header__icon--accent">
                <x-icon path="ph.regular.globe" />
            </div>
            <h1>{{ __('install.languages.heading') }}</h1>
            <p class="step-subtitle">{{ __('install.languages.subtitle') }}</p>
        </div>

        <div class="step-body">
            <form hx-post="{{ route('installer.step4.save') }}" hx-target="body" hx-swap="morph" id="languagesForm">
                <div class="lang-grid">
                    @foreach($allLanguages as $lang)
                        @php
                            $isCurrentLocale = $lang['code'] === config('lang.locale', 'en');
                            $isEnabled = in_array($lang['code'], $enabledLanguages, true);
                        @endphp
                        <label class="lang-card">
                            @if($isCurrentLocale)
                                <input type="hidden" name="languages[]" value="{{ $lang['code'] }}" />
                                <input type="checkbox" checked disabled />
                            @else
                                <input type="checkbox" name="languages[]" value="{{ $lang['code'] }}" {{ $isEnabled ? 'checked' : '' }} />
                            @endif
                            <div class="lang-card__body">
                                <img class="lang-card__flag" src="{{ asset('assets/img/langs/' . $lang['flag'] . '.svg') }}" alt="{{ $lang['code'] }}" />
                                <div class="lang-card__info">
                                    <div class="lang-card__name">{{ $lang['native'] }}</div>
                                    <div class="lang-card__code">{{ strtoupper($lang['code']) }}{{ $isCurrentLocale ? ' — ' . __('install.languages.current') : '' }}</div>
                                </div>
                                <span class="lang-card__check"></span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <a href="{{ route('installer.step', ['id' => 3]) }}" class="btn btn--link" hx-boost="true">
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
