@php
    $marketUrl = rtrim(config('app.flute_market_url', 'https://flute-cms.com'), '/');
    $hasModules = !empty($recommended) || !empty($modules);
@endphp
<div class="modules-step">
    <div class="step-panel">
        <div class="step-header">
            <div class="step-header__icon step-header__icon--blue">
                <x-icon path="ph.regular.package" />
            </div>
            <h1>{{ __('install.modules.heading') }}</h1>
            <p class="step-subtitle">{{ __('install.modules.subtitle') }}</p>
        </div>

        <div class="step-body">
            <form hx-post="{{ route('installer.step5.save') }}" hx-target="body" hx-swap="morph" id="modulesForm">

                @if(!empty($modulesError))
                    <div class="alert alert--warning">{{ $modulesError }}</div>
                @elseif(!empty($noKey))
                    <div class="info-card">
                        <div class="info-card__icon">
                            <x-icon path="ph.regular.key" />
                        </div>
                        <div class="info-card__content">
                            <h4>{{ __('install.modules.no_key') }}</h4>
                            <p>{{ __('install.modules.no_key_desc') }}</p>
                        </div>
                    </div>
                @elseif($hasModules)

                    {{-- ── Recommended (always visible, no scroll) ────── --}}
                    @if(!empty($recommended))
                        <div class="modules-featured">
                            <div class="modules-section__label">
                                <x-icon path="ph.regular.star" />
                                {{ __('install.modules.recommended_label') }}
                            </div>
                            <div class="featured-grid">
                                @foreach($recommended as $module)
                                    @php
                                        $slug = $module['name'] ?? $module['slug'] ?? '';
                                        $imgUrl = '';
                                        if (!empty($module['primaryImage'])) {
                                            $imgUrl = str_starts_with($module['primaryImage'], 'http')
                                                ? $module['primaryImage']
                                                : $marketUrl . $module['primaryImage'];
                                        }
                                    @endphp
                                    <label class="featured-card">
                                        <input type="checkbox" name="modules[]" value="{{ $slug }}" checked />
                                        <div class="featured-card__body">
                                            <div class="featured-card__img">
                                                @if($imgUrl)
                                                    <img loading="lazy" src="{{ $imgUrl }}" alt="{{ $slug }}" />
                                                @else
                                                    <div class="featured-card__placeholder">
                                                        <x-icon path="ph.regular.puzzle-piece" />
                                                    </div>
                                                @endif
                                                <span class="featured-card__badge">
                                                    <x-icon path="ph.bold.star" />
                                                    {{ __('install.modules.recommended_label') }}
                                                </span>
                                            </div>
                                            <div class="featured-card__row">
                                                <div class="featured-card__info">
                                                    <div class="featured-card__name">{{ $slug }}</div>
                                                    <div class="featured-card__desc">{{ mb_substr($module['description'] ?? '', 0, 80) }}{{ mb_strlen($module['description'] ?? '') > 80 ? '...' : '' }}</div>
                                                </div>
                                                <span class="featured-card__check"></span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ── All modules (search + scrollable list) ─────── --}}
                    @if(!empty($modules))
                        <div class="modules-browse">
                            <div class="modules-section__label">
                                <x-icon path="ph.regular.squares-four" />
                                {{ __('install.modules.all_label') }}
                            </div>

                            <div class="module-search">
                                <x-icon path="ph.regular.magnifying-glass" />
                                <input
                                    type="text"
                                    class="module-search__input"
                                    placeholder="{{ __('install.modules.search') }}"
                                    data-module-search
                                />
                                <span class="module-search__count" data-module-count>{{ count($modules) }}</span>
                            </div>

                            <div class="module-list" data-module-list>
                                @foreach($modules as $module)
                                    @php
                                        $slug = $module['name'] ?? $module['slug'] ?? '';
                                        $imgUrl = '';
                                        if (!empty($module['primaryImage'])) {
                                            $imgUrl = str_starts_with($module['primaryImage'], 'http')
                                                ? $module['primaryImage']
                                                : $marketUrl . $module['primaryImage'];
                                        }
                                    @endphp
                                    <label class="module-card" data-module-name="{{ mb_strtolower($slug) }}" data-module-desc="{{ mb_strtolower($module['description'] ?? '') }}">
                                        <input type="checkbox" name="modules[]" value="{{ $slug }}" />
                                        <div class="module-card__body">
                                            <div class="module-card__icon">
                                                @if($imgUrl)
                                                    <img loading="lazy" src="{{ $imgUrl }}" alt="{{ $slug }}" />
                                                @else
                                                    <x-icon path="ph.regular.puzzle-piece" />
                                                @endif
                                            </div>
                                            <div class="module-card__info">
                                                <div class="module-card__name">{{ $slug }}</div>
                                                <div class="module-card__desc">{{ $module['description'] ?? '' }}</div>
                                            </div>
                                            <span class="module-card__check"></span>
                                        </div>
                                    </label>
                                @endforeach

                                <div class="modules-empty modules-empty--search" data-module-empty style="display:none">
                                    <x-icon path="ph.regular.magnifying-glass" />
                                    <p>{{ __('install.modules.not_found') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    <div class="modules-empty">
                        <x-icon path="ph.regular.package" />
                        <p>{{ __('install.modules.empty') }}</p>
                    </div>
                @endif

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <a href="{{ route('installer.step', ['id' => 4]) }}" class="btn btn--link" hx-boost="true">
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
