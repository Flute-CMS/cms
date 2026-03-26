@php
    $marketUrl = rtrim(config('app.flute_market_url', 'https://flute-cms.com'), '/');
    $recommendedSlugs = ['Monitoring', 'SteamFriends', 'MiniBalance', 'SteamInfo'];
@endphp
<div class="customize-step">
    <form hx-post="{{ route('installer.step4.save') }}" hx-target="body" hx-swap="morph" id="customizeForm">

        {{-- ── Sub-page 1: Languages ─────────────────────────────── --}}
        <div class="customize-page" data-customize-page="1">
            <div class="step-panel">
                <div class="step-header">
                    <div class="step-header__icon step-header__icon--accent">
                        <x-icon path="ph.regular.globe" />
                    </div>
                    <h1>{{ __('install.customize.languages_heading') }}</h1>
                    <p class="step-subtitle">{{ __('install.customize.languages_subtitle') }}</p>
                </div>

                <div class="step-body">
                    <div class="option-grid">
                        @foreach($allLanguages as $lang)
                            @php
                                $isCurrentLocale = $lang['code'] === config('lang.locale', 'en');
                                $isEnabled = in_array($lang['code'], $enabledLanguages, true);
                            @endphp
                            <label class="option-card">
                                @if($isCurrentLocale)
                                    <input type="hidden" name="languages[]" value="{{ $lang['code'] }}" />
                                    <input type="checkbox" checked disabled />
                                @else
                                    <input type="checkbox" name="languages[]" value="{{ $lang['code'] }}" {{ $isEnabled ? 'checked' : '' }} />
                                @endif
                                <div class="option-card__body option-card__body--row">
                                    <span class="option-card__flag">{{ $lang['flag'] }}</span>
                                    <div class="option-card__info">
                                        <div class="option-card__title">{{ $lang['native'] }}</div>
                                        <div class="option-card__desc">{{ strtoupper($lang['code']) }}{{ $isCurrentLocale ? ' — ' . __('install.customize.current_language') : '' }}</div>
                                    </div>
                                    <span class="option-card__check"></span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <button
                            type="button"
                            class="btn btn--link"
                            hx-get="{{ route('installer.step', ['id' => 3]) }}"
                            hx-target="body"
                            hx-push-url="true"
                        >
                            <span class="btn__spinner"></span>
                            <span class="btn__label">
                                <x-icon path="ph.regular.caret-left" />
                                {{ __('install.common.back') }}
                            </span>
                        </button>
                        <button type="button" class="btn btn--primary" data-customize-next>
                            <span class="btn__label">
                                {{ __('install.common.next') }}
                                <x-icon path="ph.regular.arrow-right" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sub-page 2: Modules ───────────────────────────────── --}}
        <div class="customize-page" data-customize-page="2" style="display:none">
            <div class="step-panel">
                <div class="step-header">
                    <div class="step-header__icon step-header__icon--blue">
                        <x-icon path="ph.regular.package" />
                    </div>
                    <h1>{{ __('install.customize.modules_heading') }}</h1>
                    <p class="step-subtitle">{{ __('install.customize.modules_subtitle') }}</p>
                </div>

                <div class="step-body">
                    @if(!empty($modulesError))
                        <div class="alert alert--warning">{{ $modulesError }}</div>
                    @elseif(!empty($noKey))
                        <div class="info-card">
                            <div class="info-card__icon">
                                <x-icon path="ph.regular.key" />
                            </div>
                            <div class="info-card__content">
                                <h4>{{ __('install.customize.modules_no_key') }}</h4>
                                <p>{{ __('install.customize.modules_no_key_desc') }}</p>
                            </div>
                        </div>
                    @elseif(!empty($modules))
                        <div class="module-search">
                            <x-icon path="ph.regular.magnifying-glass" />
                            <input
                                type="text"
                                class="module-search__input"
                                placeholder="{{ __('install.customize.modules_search') }}"
                                data-module-search
                            />
                            <span class="module-search__count" data-module-count>{{ count($modules) }}</span>
                        </div>

                        <div class="module-list" data-module-list>
                            @foreach($modules as $module)
                                @php
                                    $slug = $module['name'] ?? $module['slug'] ?? '';
                                    $isRecommended = in_array($slug, $recommendedSlugs, true);
                                    $imgUrl = '';
                                    if (!empty($module['primaryImage'])) {
                                        $imgUrl = str_starts_with($module['primaryImage'], 'http')
                                            ? $module['primaryImage']
                                            : $marketUrl . $module['primaryImage'];
                                    }
                                @endphp
                                <label class="module-card" data-module-name="{{ mb_strtolower($slug) }}" data-module-desc="{{ mb_strtolower($module['description'] ?? '') }}">
                                    <input type="checkbox" name="modules[]" value="{{ $slug }}" {{ $isRecommended ? 'checked' : '' }} />
                                    <div class="module-card__body">
                                        <div class="module-card__preview">
                                            @if($imgUrl)
                                                <img loading="lazy" src="{{ $imgUrl }}" alt="{{ $slug }}" />
                                            @else
                                                <div class="module-card__placeholder">
                                                    <x-icon path="ph.regular.puzzle-piece" />
                                                </div>
                                            @endif
                                        </div>
                                        <div class="module-card__info">
                                            <div class="module-card__name">
                                                {{ $slug }}
                                                @if($isRecommended)
                                                    <span class="module-badge">{{ __('install.customize.recommended') }}</span>
                                                @endif
                                            </div>
                                            <div class="module-card__desc">{{ mb_substr($module['description'] ?? '', 0, 100) }}{{ mb_strlen($module['description'] ?? '') > 100 ? '...' : '' }}</div>
                                        </div>
                                        <span class="module-card__check"></span>
                                    </div>
                                </label>
                            @endforeach

                            <div class="modules-empty modules-empty--search" data-module-empty style="display:none">
                                <x-icon path="ph.regular.magnifying-glass" />
                                <p>{{ __('install.customize.modules_not_found') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="modules-empty">
                            <x-icon path="ph.regular.package" />
                            <p>{{ __('install.customize.modules_empty') }}</p>
                        </div>
                    @endif
                </div>

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <button type="button" class="btn btn--link" data-customize-prev>
                            <span class="btn__label">
                                <x-icon path="ph.regular.caret-left" />
                                {{ __('install.common.back') }}
                            </span>
                        </button>
                        <button type="submit" class="btn btn--primary">
                            <span class="btn__spinner"></span>
                            <span class="btn__label">
                                {{ __('install.common.next') }}
                                <x-icon path="ph.regular.arrow-right" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
