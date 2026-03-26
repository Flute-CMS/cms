<div class="launch-step">
    <form hx-post="{{ route('installer.step6.save') }}" hx-target=".installer-content__inner" hx-select=".launch-step" hx-swap="innerHTML" id="launchForm">

        {{-- ── Sub-step 1: Appearance ──────────────────────────── --}}
        <div class="launch-page" data-launch-page="1">
            <div class="step-panel">
                <div class="step-header">
                    <div class="step-header__icon step-header__icon--accent">
                        <x-icon path="ph.regular.palette" />
                    </div>
                    <h1>{{ __('install.launch.appearance_heading') }}</h1>
                    <p class="step-subtitle">{{ __('install.launch.appearance_subtitle') }}</p>
                </div>

                <div class="step-body">
                    <div class="theme-picker">
                        <label class="theme-pick">
                            <input type="radio" name="default_theme" value="dark" {{ $default_theme === 'dark' ? 'checked' : '' }} />
                            <div class="theme-pick__preview theme-pick__preview--dark">
                                <div class="theme-pick__header">
                                    <span></span><span></span><span></span>
                                </div>
                                <div class="theme-pick__body">
                                    <div class="theme-pick__sidebar">
                                        <div class="theme-pick__nav"></div>
                                        <div class="theme-pick__nav theme-pick__nav--on"></div>
                                        <div class="theme-pick__nav"></div>
                                    </div>
                                    <div class="theme-pick__main">
                                        <div class="theme-pick__line"></div>
                                        <div class="theme-pick__line theme-pick__line--sm"></div>
                                        <div class="theme-pick__block"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="theme-pick__label">
                                <span class="theme-pick__dot"></span>
                                {{ __('install.launch.theme_dark') }}
                            </span>
                        </label>

                        <label class="theme-pick">
                            <input type="radio" name="default_theme" value="light" {{ $default_theme === 'light' ? 'checked' : '' }} />
                            <div class="theme-pick__preview theme-pick__preview--light">
                                <div class="theme-pick__header">
                                    <span></span><span></span><span></span>
                                </div>
                                <div class="theme-pick__body">
                                    <div class="theme-pick__sidebar">
                                        <div class="theme-pick__nav"></div>
                                        <div class="theme-pick__nav theme-pick__nav--on"></div>
                                        <div class="theme-pick__nav"></div>
                                    </div>
                                    <div class="theme-pick__main">
                                        <div class="theme-pick__line"></div>
                                        <div class="theme-pick__line theme-pick__line--sm"></div>
                                        <div class="theme-pick__block"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="theme-pick__label">
                                <span class="theme-pick__dot"></span>
                                {{ __('install.launch.theme_light') }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <a href="{{ route('installer.step', ['id' => 5]) }}" class="btn btn--link" hx-boost="true">
                            <span class="btn__label">
                                <x-icon path="ph.regular.caret-left" />
                                {{ __('install.common.back') }}
                            </span>
                        </a>
                        <button type="button" class="btn btn--primary" data-launch-next>
                            <span class="btn__label">
                                {{ __('install.common.next') }}
                                <x-icon path="ph.regular.arrow-right" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sub-step 2: Features & Visibility ──────────────── --}}
        <div class="launch-page" data-launch-page="2" style="display:none">
            <div class="step-panel">
                <div class="step-header">
                    <div class="step-header__icon step-header__icon--blue">
                        <x-icon path="ph.regular.sliders-horizontal" />
                    </div>
                    <h1>{{ __('install.launch.features_heading') }}</h1>
                    <p class="step-subtitle">{{ __('install.launch.features_subtitle') }}</p>
                </div>

                <div class="step-body">
                    <div class="option-grid">
                        <label class="option-card">
                            <input type="hidden" name="csrf_enabled" value="0" />
                            <input type="checkbox" name="csrf_enabled" value="1" {{ $csrf_enabled ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--green">
                                    <x-icon path="ph.regular.shield-check" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.csrf_enabled') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.csrf_enabled_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="convert_to_webp" value="0" />
                            <input type="checkbox" name="convert_to_webp" value="1" {{ $convert_to_webp ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--purple">
                                    <x-icon path="ph.regular.image" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.convert_to_webp') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.convert_to_webp_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="change_theme" value="0" />
                            <input type="checkbox" name="change_theme" value="1" {{ $change_theme ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--orange">
                                    <x-icon path="ph.regular.sun-dim" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.change_theme') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.change_theme_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="is_performance" value="0" />
                            <input type="checkbox" name="is_performance" value="1" {{ $is_performance ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--yellow">
                                    <x-icon path="ph.regular.lightning" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.is_performance') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.is_performance_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="cron_mode" value="0" />
                            <input type="checkbox" name="cron_mode" value="1" {{ $cron_mode ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--blue">
                                    <x-icon path="ph.regular.clock-clockwise" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.cron_mode') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.cron_mode_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="tips" value="0" />
                            <input type="checkbox" name="tips" value="1" {{ $tips ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--amber">
                                    <x-icon path="ph.regular.lightbulb" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.tips') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.tips_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="share" value="0" />
                            <input type="checkbox" name="share" value="1" {{ $share ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--teal">
                                    <x-icon path="ph.regular.bug" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.share') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.share_desc') }}</div>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="hidden" name="maintenance_mode" value="0" />
                            <input type="checkbox" name="maintenance_mode" value="1" {{ $maintenance_mode ? 'checked' : '' }} />
                            <div class="option-card__body">
                                <span class="option-card__check"></span>
                                <div class="option-card__icon option-card__icon--red">
                                    <x-icon path="ph.regular.wrench" />
                                </div>
                                <div class="option-card__title">{{ __('install.launch.maintenance_mode') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.maintenance_mode_desc') }}</div>
                            </div>
                        </label>
                    </div>

                    <hr class="divider" />

                    {{-- Search visibility --}}
                    <div class="launch-section">
                        <div class="launch-section__header">
                            <x-icon path="ph.regular.magnifying-glass" />
                            <span>{{ __('install.launch.section_visibility') }}</span>
                        </div>

                        <div class="radio-grid radio-grid--2">
                            <label class="radio-card">
                                <input type="radio" name="robots" value="index, follow" {{ $robots === 'index, follow' ? 'checked' : '' }} />
                                <div class="radio-card__body">
                                    <span class="radio-card__dot"></span>
                                    <div class="radio-card__text">
                                        <strong>{{ __('install.launch.robots_index') }}</strong>
                                        <span>{{ __('install.launch.robots_index_desc') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="robots" value="noindex, nofollow" {{ $robots === 'noindex, nofollow' ? 'checked' : '' }} />
                                <div class="radio-card__body">
                                    <span class="radio-card__dot"></span>
                                    <div class="radio-card__text">
                                        <strong>{{ __('install.launch.robots_noindex') }}</strong>
                                        <span>{{ __('install.launch.robots_noindex_desc') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <button type="button" class="btn btn--link" data-launch-prev>
                            <span class="btn__label">
                                <x-icon path="ph.regular.caret-left" />
                                {{ __('install.common.back') }}
                            </span>
                        </button>
                        <button type="button" class="btn btn--primary" data-launch-next>
                            <span class="btn__label">
                                {{ __('install.common.next') }}
                                <x-icon path="ph.regular.arrow-right" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sub-step 3: Integrations & Launch ──────────────── --}}
        <div class="launch-page" data-launch-page="3" style="display:none">
            <div class="step-panel">
                <div class="step-header">
                    <div class="step-header__icon step-header__icon--green">
                        <x-icon path="ph.regular.rocket-launch" />
                    </div>
                    <h1>{{ __('install.launch.ready_heading') }}</h1>
                    <p class="step-subtitle">{{ __('install.launch.ready_subtitle') }}</p>
                </div>

                <div class="step-body">
                    {{-- Steam API --}}
                    <div class="launch-section">
                        <div class="launch-section__header">
                            <x-icon path="ph.regular.game-controller" />
                            <span>{{ __('install.launch.steam_api_label') }}</span>
                        </div>

                        <div class="field" style="margin-bottom: 0;">
                            <input type="text" name="steam_api" class="field__input" value="{{ $steam_api }}" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" />
                            <span class="field__hint">{{ __('install.launch.steam_api_hint') }}</span>
                        </div>
                    </div>

                    <hr class="divider" />

                    {{-- Copyright --}}
                    <label class="option-card option-card--single">
                        <input type="hidden" name="flute_copyright" value="0" />
                        <input type="checkbox" name="flute_copyright" value="1" {{ $flute_copyright ? 'checked' : '' }} />
                        <div class="option-card__body option-card__body--row">
                            <div class="option-card__icon option-card__icon--accent">
                                <x-icon path="ph.regular.heart" />
                            </div>
                            <div class="option-card__info">
                                <div class="option-card__title">{{ __('install.launch.flute_copyright') }}</div>
                                <div class="option-card__desc">{{ __('install.launch.flute_copyright_desc') }}</div>
                            </div>
                            <span class="option-card__check"></span>
                        </div>
                    </label>
                </div>

                @if($errorMessage)
                    <div class="alert alert--danger" style="margin-top: 16px;">
                        {{ $errorMessage }}
                    </div>
                @endif

                <div class="step-footer">
                    <div class="installer-form__actions">
                        <button type="button" class="btn btn--link" data-launch-prev>
                            <span class="btn__label">
                                <x-icon path="ph.regular.caret-left" />
                                {{ __('install.common.back') }}
                            </span>
                        </button>
                        <button type="submit" class="btn btn--primary">
                            <span class="btn__spinner"></span>
                            <span class="btn__label">
                                {{ __('install.common.finish') }}
                                <x-icon path="ph.regular.rocket-launch" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
