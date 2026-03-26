<div class="welcome-step">
    <div class="welcome-step__bg">
        <div class="welcome-step__orb welcome-step__orb--1"></div>
        <div class="welcome-step__orb welcome-step__orb--2"></div>
        <div class="welcome-step__orb welcome-step__orb--3"></div>
        <div class="welcome-step__grid"></div>
        <div class="welcome-step__noise"></div>
    </div>

    <div class="welcome-step__content" id="welcome-content">
        <div class="welcome-step__logo">
            <div class="welcome-step__logo-glow"></div>
            <img src="@asset('assets/img/flute_logo.svg')" alt="Flute">
        </div>

        <div class="welcome-step__heading">
            <h1>
                {{ __('install.welcome.heading_line1') }}
                <br>
                <span class="text-accent">Flute CMS</span>
            </h1>
        </div>

        <div class="welcome-step__desc">
            <p>{{ __('install.welcome.subtitle') }}</p>
        </div>

        <div class="welcome-step__lang">
            <label class="field__label">
                {{ __('install.welcome.language_label') }}
            </label>
            <div class="welcome-step__lang-grid">
                @foreach($languages as $lang)
                    @php
                        $langName = __("langs.{$lang}");
                        if ($langName === "langs.{$lang}") {
                            $langName = strtoupper($lang);
                        }
                        $flagOverrides = ['cs' => 'cz'];
                        $flagCode = $flagOverrides[$lang] ?? $lang;
                    @endphp
                    <button
                        type="button"
                        class="lang-chip {{ $selectedLanguage === $lang ? 'lang-chip--active' : '' }}"
                        hx-post="{{ route('installer.welcome') }}"
                        hx-vals='{"action":"setLanguage","language":"{{ $lang }}"}'
                        hx-target="#welcome-content"
                        hx-select="#welcome-content"
                        hx-swap="outerHTML"
                    >
                        <img class="lang-chip__flag" src="@asset("assets/img/langs/{$flagCode}.svg")" alt="{{ $lang }}" />
                        <span class="lang-chip__name">{{ $langName }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <form class="welcome-step__form"
              hx-post="{{ route('installer.welcome') }}"
              hx-target="body"
              hx-swap="innerHTML"
              hx-push-url="true"
        >
            <input type="hidden" name="action" value="validateAndProceed">

            <div class="accordion" data-accordion>
                <button type="button" class="accordion__header" data-accordion-trigger>
                    <span class="accordion__left">
                        <span class="accordion__icon">
                            <x-icon path="ph.regular.key" />
                        </span>
                        <span class="accordion__title">{{ __('install.welcome.key_label') }}</span>
                        <span class="accordion__badge">{{ __('install.welcome.key_optional') }}</span>
                    </span>
                    <svg class="accordion__chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="accordion__panel" data-accordion-panel>
                    <div class="accordion__body">
                        <div class="field">
                            <input
                                type="text"
                                name="fluteKey"
                                class="field__input field__input--mono"
                                placeholder="{{ __('install.welcome.key_placeholder') }}"
                                value="{{ $fluteKey }}"
                            />
                        </div>
                        <p class="field__hint">{{ __('install.welcome.key_hint') }}</p>

                        @if($keyError)
                            <div class="alert alert--danger" style="margin-top: 8px;">{{ $keyError }}</div>
                        @endif

                        @if($keyValid && ! empty($fluteKey))
                            <div class="alert alert--success" style="margin-top: 8px;">{{ __('install.welcome.key_success') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="welcome-step__action">
                <button type="submit" class="btn btn--primary btn--lg welcome-step__cta">
                    <span class="btn__spinner"></span>
                    <span class="btn__label">
                        {{ __('install.welcome.get_started') }}
                        <x-icon path="ph.regular.arrow-right" />
                    </span>
                </button>
            </div>
        </form>

        <div class="welcome-step__footer">
            <span class="welcome-step__version">v{{ Flute\Core\App::VERSION }}</span>
            <span class="welcome-step__sep">&middot;</span>
            <span class="welcome-step__author">
                Made with ❤️ by <a href="https://github.com/flamesONE" target="_blank" rel="noopener">Flames</a>
            </span>
        </div>
    </div>
</div>
