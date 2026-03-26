<aside class="ve" id="visual-editor">
    <header class="ve__header">
        <div class="ve__header-title">
            <span class="ve__header-icon">
                <x-icon path="ph.regular.sliders-horizontal" />
            </span>
            {{ __('page-edit.editor_title') }}
        </div>
        <div class="ve__header-actions">
            <button type="button" class="ve__icon-btn" id="ve-undo" disabled title="{{ __('def.undo') }}">
                <x-icon path="ph.regular.arrow-u-up-left" />
            </button>
            <button type="button" class="ve__icon-btn" id="ve-redo" disabled title="{{ __('def.redo') }}">
                <x-icon path="ph.regular.arrow-u-up-right" />
            </button>
            <button type="button" class="ve__icon-btn ve__close" id="visual-editor-close">
                <x-icon path="ph.regular.x" />
            </button>
        </div>
    </header>

    @if(app(\Flute\Core\Theme\ThemeManager::class)->getCurrentTheme() !== 'standard')
        <div class="ve__alert ve__alert--warning">
            <div class="ve__alert-icon">
                <x-icon path="ph.regular.warning" />
            </div>
            <div class="ve__alert-content">
                <div class="ve__alert-title">{{ __('page-edit.custom_theme_warning') }}</div>
                <div class="ve__alert-desc">{{ __('page-edit.custom_theme_warning_desc') }}</div>
            </div>
        </div>
    @endif

    <nav class="ve__tabs">
        <button type="button" class="ve__tab active" data-ve-tab="palette" title="{{ __('page-edit.theme_colors') }}">
            <x-icon path="ph.regular.palette" />
        </button>
        <button type="button" class="ve__tab" data-ve-tab="typography" title="{{ __('page-edit.typography') }}">
            <x-icon path="ph.regular.text-aa" />
        </button>
        <button type="button" class="ve__tab" data-ve-tab="layout" title="{{ __('page-edit.layout') }}">
            <x-icon path="ph.regular.layout" />
        </button>
        <button type="button" class="ve__tab" data-ve-tab="background" title="{{ __('page-edit.background_section') }}">
            <x-icon path="ph.regular.image" />
        </button>
        <button type="button" class="ve__tab" data-ve-tab="effects" title="{{ __('page-edit.effects_section') }}">
            <x-icon path="ph.regular.sparkle" />
        </button>
    </nav>

    <div class="ve__body">

        {{-- ===== 1. Palette ===== --}}
        <div class="ve__panel active" data-ve-panel="palette">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.color_presets') }}</h4>
                <div class="ve__color-presets" id="ve-color-presets"></div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.theme_colors') }}</h4>
                <div class="ve__colors">
                    @foreach (['accent', 'primary', 'secondary', 'background', 'text'] as $color)
                        <label class="ve__color" data-variable="--{{ $color }}">
                            <span class="ve__color-preview" style="background: var(--{{ $color }});"></span>
                            <input type="color" class="ve__color-input" />
                            <span class="ve__color-name">{{ __('page-edit.' . $color) }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="ve__section">
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.border_radius') }}</label>
                        <div class="ve__row" style="gap: 8px; align-items: center;">
                            <div class="ve__radius-preview" id="ve-border-preview"></div>
                            <span class="ve__range-val">1rem</span>
                        </div>
                    </div>
                    <input type="range" class="ve__range" id="ve-border-radius" data-variable="--border1"
                        min="0" max="2" step="0.125" value="1" data-unit="rem" />
                </div>
            </section>
        </div>

        {{-- ===== 2. Typography ===== --}}
        <div class="ve__panel" data-ve-panel="typography">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.font_family') }}</h4>
                <div class="ve__font-cards" id="ve-font-cards" data-variable="--font">
                    @foreach (['Manrope', 'Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Poppins', 'Nunito', 'Raleway', 'Ubuntu', 'Rubik', 'Work Sans', 'DM Sans', 'Outfit', 'Plus Jakarta Sans', 'Space Grotesk', 'Lexend', 'Sora', 'Urbanist', 'Figtree', 'Lato', 'Onest', 'Albert Sans', 'Instrument Sans', 'Gabarito', 'Geologica', 'Red Hat Display', 'Bricolage Grotesque', 'Anybody', 'Quicksand', 'Barlow', 'Karla', 'Familjen Grotesk', 'Titillium Web', 'Hanken Grotesk', 'Wix Madefor Display', 'Atkinson Hyperlegible'] as $font)
                        <label class="ve__font-card{{ $font === 'Manrope' ? ' active' : '' }}" data-font="{{ $font }}">
                            <input type="radio" name="ve-font-family" value="{{ $font }}" {{ $font === 'Manrope' ? 'checked' : '' }} />
                            <span class="ve__font-card-preview">Aa</span>
                            <span class="ve__font-card-name">{{ $font }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.heading_font') }}</h4>
                <div class="ve__font-cards" id="ve-heading-font-cards" data-variable="--font-header">
                    <label class="ve__font-card active" data-font="inherit">
                        <input type="radio" name="ve-heading-font" value="inherit" checked />
                        <span class="ve__font-card-preview">Aa</span>
                        <span class="ve__font-card-name">{{ __('page-edit.same_as_body') }}</span>
                    </label>
                    @foreach (['Manrope', 'Inter', 'Roboto', 'Montserrat', 'Poppins', 'Raleway', 'Rubik', 'Work Sans', 'DM Sans', 'Playfair Display', 'Merriweather', 'Lora', 'Bebas Neue', 'Oswald', 'Fjalla One', 'Archivo Black', 'Unbounded', 'Red Hat Display', 'Bricolage Grotesque', 'Gabarito', 'Barlow Condensed', 'Righteous', 'Bungee', 'Spectral', 'Crimson Text', 'Familjen Grotesk', 'Comfortaa', 'Wix Madefor Display'] as $font)
                        <label class="ve__font-card" data-font="{{ $font }}">
                            <input type="radio" name="ve-heading-font" value="{{ $font }}" />
                            <span class="ve__font-card-preview">Aa</span>
                            <span class="ve__font-card-name">{{ $font }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="ve__section">
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.font_scale') }}</label>
                        <span class="ve__range-val" id="ve-font-scale-val">1.15</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-font-scale" data-variable="--font-scale"
                        min="1.0" max="1.35" step="0.05" value="1.15" />
                </div>
            </section>

            <div class="ve__preview-card">
                <h3 class="ve__preview-h">{{ __('page-edit.preview_heading') }}</h3>
                <p class="ve__preview-p">{{ __('page-edit.preview_text') }}</p>
            </div>
        </div>

        {{-- ===== 3. Layout ===== --}}
        <div class="ve__panel" data-ve-panel="layout">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.nav_style') }}</h4>
                <div class="ve__option-cards ve__option-cards--nav-3col">
                    <button type="button" class="ve__option-card active" data-nav-style="default" title="{{ __('page-edit.nav_style_default') }}">
                        <div class="ve__option-preview ve__option-preview--nav-default">
                            <div class="ve__preview-navbar ve__preview-navbar--full"></div>
                            <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.nav_default') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-nav-style="pill" title="{{ __('page-edit.nav_style_pill') }}">
                        <div class="ve__option-preview ve__option-preview--nav-pill">
                            <div class="ve__preview-navbar ve__preview-navbar--floating"></div>
                            <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.nav_pill') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-nav-style="pill-transparent" title="{{ __('page-edit.nav_style_pill_transparent') }}">
                        <div class="ve__option-preview ve__option-preview--nav-pill-transparent">
                            <div class="ve__preview-navbar ve__preview-navbar--transparent"></div>
                            <div class="ve__preview-content"><div class="ve__preview-block ve__preview-block--hero"></div><div class="ve__preview-block"></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.nav_pill_transparent') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-nav-style="pill-full" title="{{ __('page-edit.nav_style_pill_full') }}">
                        <div class="ve__option-preview ve__option-preview--nav-pill-full">
                            <div class="ve__preview-navbar ve__preview-navbar--pill-full"></div>
                            <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.nav_pill_full') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-nav-style="sidebar" title="{{ __('page-edit.nav_style_sidebar') }}">
                        <div class="ve__option-preview ve__option-preview--nav-sidebar">
                            <div class="ve__preview-sidebar"></div>
                            <div class="ve__preview-main">
                                <div class="ve__preview-navbar ve__preview-navbar--slim"></div>
                                <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                            </div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.nav_sidebar') }}</span>
                    </button>
                </div>
            </section>

            <div class="ve__sidebar-styles" id="ve-sidebar-styles" hidden>
                <section class="ve__section">
                    <h4 class="ve__section-title">{{ __('page-edit.sidebar_style') }}</h4>
                    <div class="ve__option-cards ve__option-cards--nav-2col">
                        <button type="button" class="ve__option-card active" data-sidebar-style="default" title="{{ __('page-edit.sidebar_style_default') }}">
                            <div class="ve__option-preview ve__option-preview--sidebar-default">
                                <div class="ve__preview-sidebar ve__preview-sidebar--full">
                                    <div class="ve__preview-sidebar-logo"></div>
                                    <div class="ve__preview-sidebar-items">
                                        <div class="ve__preview-sidebar-item"></div>
                                        <div class="ve__preview-sidebar-item"></div>
                                        <div class="ve__preview-sidebar-item"></div>
                                    </div>
                                </div>
                                <div class="ve__preview-main"><div class="ve__preview-block"></div></div>
                            </div>
                            <span class="ve__option-label">{{ __('page-edit.sidebar_default') }}</span>
                        </button>
                        <button type="button" class="ve__option-card" data-sidebar-style="mini" title="{{ __('page-edit.sidebar_style_mini') }}">
                            <div class="ve__option-preview ve__option-preview--sidebar-mini">
                                <div class="ve__preview-sidebar ve__preview-sidebar--mini">
                                    <div class="ve__preview-sidebar-icon"></div>
                                    <div class="ve__preview-sidebar-icon"></div>
                                    <div class="ve__preview-sidebar-icon"></div>
                                </div>
                                <div class="ve__preview-main"><div class="ve__preview-block"></div></div>
                            </div>
                            <span class="ve__option-label">{{ __('page-edit.sidebar_mini') }}</span>
                        </button>
                    </div>

                    <div class="ve__field ve__field--switch" id="ve-sidebar-contained-wrap" style="margin-top: var(--space-md);">
                        <label class="ve__field-label">{{ __('page-edit.sidebar_contained') }}</label>
                        <x-fields.toggle name="ve-sidebar-contained" id="ve-sidebar-contained" :checked="false" />
                    </div>

                    <div class="ve__sidebar-mode" id="ve-sidebar-mode">
                        <h4 class="ve__section-title" style="margin-top: var(--space-md);">{{ __('page-edit.sidebar_mode') }}</h4>
                        <div class="ve__option-cards ve__option-cards--nav-2col">
                            <button type="button" class="ve__option-card" data-sidebar-mode="minimal" title="{{ __('page-edit.sidebar_mode_minimal') }}">
                                <div class="ve__option-preview ve__option-preview--sidebar-minimal">
                                    <div class="ve__preview-sidebar ve__preview-sidebar--full">
                                        <div class="ve__preview-sidebar-item ve__preview-sidebar-item--minimal"></div>
                                        <div class="ve__preview-sidebar-item ve__preview-sidebar-item--minimal"></div>
                                        <div class="ve__preview-sidebar-item ve__preview-sidebar-item--minimal"></div>
                                    </div>
                                </div>
                                <span class="ve__option-label">{{ __('page-edit.minimal') }}</span>
                            </button>
                            <button type="button" class="ve__option-card active" data-sidebar-mode="full" title="{{ __('page-edit.sidebar_mode_full') }}">
                                <div class="ve__option-preview ve__option-preview--sidebar-full">
                                    <div class="ve__preview-sidebar ve__preview-sidebar--full">
                                        <div class="ve__preview-sidebar-item ve__preview-sidebar-item--active"></div>
                                        <div class="ve__preview-sidebar-item"></div>
                                        <div class="ve__preview-sidebar-item"></div>
                                    </div>
                                </div>
                                <span class="ve__option-label">{{ __('page-edit.full') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="ve__sidebar-position" id="ve-sidebar-position" hidden>
                        <h4 class="ve__section-title" style="margin-top: var(--space-md);">{{ __('page-edit.sidebar_position') }}</h4>
                        <div class="ve__option-cards ve__option-cards--nav-2col">
                            <button type="button" class="ve__option-card active" data-sidebar-position="top" title="{{ __('page-edit.sidebar_position_top') }}">
                                <div class="ve__option-preview ve__option-preview--sidebar-position-top">
                                    <div class="ve__preview-sidebar ve__preview-sidebar--mini">
                                        <div class="ve__preview-sidebar-icon"></div>
                                        <div class="ve__preview-sidebar-icon"></div>
                                        <div class="ve__preview-sidebar-icon"></div>
                                    </div>
                                </div>
                                <span class="ve__option-label">{{ __('page-edit.position_top') }}</span>
                            </button>
                            <button type="button" class="ve__option-card" data-sidebar-position="center" title="{{ __('page-edit.sidebar_position_center') }}">
                                <div class="ve__option-preview ve__option-preview--sidebar-position-center">
                                    <div class="ve__preview-sidebar ve__preview-sidebar--mini">
                                        <div class="ve__preview-sidebar-icon"></div>
                                        <div class="ve__preview-sidebar-icon"></div>
                                        <div class="ve__preview-sidebar-icon"></div>
                                    </div>
                                </div>
                                <span class="ve__option-label">{{ __('page-edit.position_center') }}</span>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <section class="ve__section">
                <div class="ve__field ve__field--switch" id="ve-nav-fixed-wrap">
                    <label class="ve__field-label">{{ __('page-edit.nav_fixed') }}</label>
                    <x-fields.toggle name="ve-nav-fixed" id="ve-nav-fixed" :checked="true" />
                </div>
                <div class="ve__field ve__field--switch" id="ve-nav-blur-wrap">
                    <label class="ve__field-label">{{ __('page-edit.nav_blur') }}</label>
                    <x-fields.toggle name="ve-nav-blur" id="ve-nav-blur" :checked="true" />
                </div>
                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.nav_show_socials') }}</label>
                    <x-fields.toggle name="ve-nav-socials" id="ve-nav-socials" :checked="true" />
                </div>
            </section>

            <section class="ve__section ve__section--divider">
                <h4 class="ve__section-title">{{ __('page-edit.footer') }}</h4>
                <div class="ve__option-cards ve__option-cards--footer-3col">
                    <button type="button" class="ve__option-card active" data-footer-type="default" title="{{ __('page-edit.footer_default') }}">
                        <div class="ve__option-preview ve__option-preview--footer-default">
                            <div class="ve__preview-content"><div class="ve__preview-block"></div></div>
                            <div class="ve__preview-footer"><div class="ve__preview-footer-cols"><div class="ve__preview-footer-col"></div><div class="ve__preview-footer-col"></div><div class="ve__preview-footer-col"></div></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.default') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-footer-type="minimal" title="{{ __('page-edit.footer_minimal') }}">
                        <div class="ve__option-preview ve__option-preview--footer-minimal">
                            <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                            <div class="ve__preview-footer ve__preview-footer--minimal"></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.minimal') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-footer-type="expanded" title="{{ __('page-edit.footer_expanded') }}">
                        <div class="ve__option-preview ve__option-preview--footer-expanded">
                            <div class="ve__preview-content"><div class="ve__preview-block"></div></div>
                            <div class="ve__preview-footer ve__preview-footer--expanded"><div class="ve__preview-footer-cols"><div class="ve__preview-footer-col ve__preview-footer-col--wide"></div><div class="ve__preview-footer-col"></div><div class="ve__preview-footer-col"></div><div class="ve__preview-footer-col"></div></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.expanded') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-footer-type="glass" title="{{ __('page-edit.footer_glass') }}">
                        <div class="ve__option-preview ve__option-preview--footer-glass">
                            <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                            <div class="ve__preview-footer ve__preview-footer--glass"></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.glass') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-footer-type="centered" title="{{ __('page-edit.footer_centered') }}">
                        <div class="ve__option-preview ve__option-preview--footer-centered">
                            <div class="ve__preview-content"><div class="ve__preview-block"></div></div>
                            <div class="ve__preview-footer ve__preview-footer--centered"><div class="ve__preview-footer-center"></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.centered') }}</span>
                    </button>
                    <button type="button" class="ve__option-card" data-footer-type="hidden" title="{{ __('page-edit.footer_hidden') }}">
                        <div class="ve__option-preview ve__option-preview--footer-hidden">
                            <div class="ve__preview-content"><div class="ve__preview-block"></div><div class="ve__preview-block"></div><div class="ve__preview-block"></div></div>
                        </div>
                        <span class="ve__option-label">{{ __('page-edit.hidden') }}</span>
                    </button>
                </div>

                <div class="ve__field ve__field--switch" style="margin-top: var(--space-md);">
                    <label class="ve__field-label">{{ __('page-edit.show_socials') }}</label>
                    <x-fields.toggle name="ve-footer-socials" id="ve-footer-socials" :checked="true" />
                </div>
                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.show_logo') }}</label>
                    <x-fields.toggle name="ve-footer-logo" id="ve-footer-logo" :checked="true" />
                </div>
            </section>

            <section class="ve__section ve__section--divider">
                <h4 class="ve__section-title">{{ __('page-edit.spacing_and_layout') }}</h4>
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.max_content_width') }}</label>
                        <span class="ve__range-val">1200px</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-max-width" data-variable="--max-content-width"
                        min="960" max="1600" step="40" value="1200" data-unit="px" />
                </div>
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.widget_gap') }}</label>
                        <span class="ve__range-val" id="ve-widget-gap-val">25px</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-widget-gap" data-variable="--widget-gap"
                        min="0" max="40" step="2" value="25" data-unit="px" />
                </div>
                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.fullwidth_mode') }}</label>
                    <x-fields.toggle name="ve-fullwidth" id="ve-fullwidth" :checked="false" />
                </div>

                @php
                    $spaces = [
                        ['id' => 've-space-xs', 'var' => '--space-xs', 'label' => 'space_xs', 'min' => 0.25, 'max' => 1, 'step' => 0.125, 'default' => 0.5],
                        ['id' => 've-space-sm', 'var' => '--space-sm', 'label' => 'space_sm', 'min' => 0.5, 'max' => 1.5, 'step' => 0.125, 'default' => 0.75],
                        ['id' => 've-space-md', 'var' => '--space-md', 'label' => 'space_md', 'min' => 0.5, 'max' => 2, 'step' => 0.25, 'default' => 1],
                        ['id' => 've-space-lg', 'var' => '--space-lg', 'label' => 'space_lg', 'min' => 1, 'max' => 3, 'step' => 0.25, 'default' => 1.5],
                        ['id' => 've-space-xl', 'var' => '--space-xl', 'label' => 'space_xl', 'min' => 1.5, 'max' => 4, 'step' => 0.25, 'default' => 2],
                    ];
                @endphp
                @foreach ($spaces as $space)
                    <div class="ve__field">
                        <div class="ve__field-row">
                            <label class="ve__field-label">{{ __('page-edit.' . $space['label']) }}</label>
                            <span class="ve__range-val">{{ $space['default'] }}rem</span>
                        </div>
                        <input type="range" class="ve__range" id="{{ $space['id'] }}"
                            data-variable="{{ $space['var'] }}" min="{{ $space['min'] }}"
                            max="{{ $space['max'] }}" step="{{ $space['step'] }}" value="{{ $space['default'] }}"
                            data-unit="rem" />
                    </div>
                @endforeach
            </section>
        </div>

        {{-- ===== 4. Background ===== --}}
        <div class="ve__panel" data-ve-panel="background">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.background_gradient') }}</h4>
                <div class="ve__gradient-types">
                    <button type="button" class="ve__gradient-type active" data-gradient-type="none" title="{{ __('page-edit.gradient_none') }}">
                        <x-icon path="ph.regular.prohibit" />
                        <span>{{ __('page-edit.bg_effect_none') }}</span>
                    </button>
                    <button type="button" class="ve__gradient-type" data-gradient-type="linear" title="{{ __('page-edit.gradient_linear') }}">
                        <x-icon path="ph.regular.gradient" />
                        <span>{{ __('page-edit.gradient_linear_short') }}</span>
                    </button>
                    <button type="button" class="ve__gradient-type" data-gradient-type="radial" title="{{ __('page-edit.gradient_radial') }}">
                        <x-icon path="ph.regular.circle" />
                        <span>{{ __('page-edit.gradient_radial_short') }}</span>
                    </button>
                    <button type="button" class="ve__gradient-type" data-gradient-type="conic" title="{{ __('page-edit.gradient_conic') }}">
                        <x-icon path="ph.regular.sun" />
                        <span>{{ __('page-edit.gradient_conic_short') }}</span>
                    </button>
                </div>

                <div class="ve__gradient-editor" id="ve-gradient-editor" hidden>
                    <div class="ve__gradient-bar-container">
                        <div class="ve__gradient-bar" id="ve-gradient-bar">
                            <div class="ve__gradient-bar-track" id="ve-gradient-bar-track"></div>
                        </div>
                        <div class="ve__gradient-bar-actions">
                            <button type="button" class="ve__gradient-bar-add" id="ve-add-gradient-stop" title="{{ __('page-edit.add_color_stop') }}">
                                <x-icon path="ph.regular.plus" />
                            </button>
                        </div>
                    </div>

                    <div class="ve__gradient-stop-editor" id="ve-gradient-stop-editor" hidden>
                        <div class="ve__gradient-stop-editor-header">
                            <span class="ve__gradient-stop-editor-title">{{ __('page-edit.color_stop') }}</span>
                            <button type="button" class="ve__gradient-stop-delete" id="ve-delete-gradient-stop" title="{{ __('page-edit.delete_stop') }}">
                                <x-icon path="ph.regular.trash" />
                            </button>
                        </div>
                        <div class="ve__gradient-stop-editor-body">
                            <label class="ve__gradient-color-picker" id="ve-stop-color-preview">
                                <input type="color" id="ve-stop-color" value="#A5FF75" />
                            </label>
                            <div class="ve__gradient-position-input">
                                <input type="number" id="ve-stop-position" value="0" min="0" max="100" />
                                <span>%</span>
                            </div>
                        </div>
                        <div class="ve__gradient-stop-opacity">
                            <label class="ve__field-label">{{ __('page-edit.opacity') }}</label>
                            <div class="ve__opacity-slider">
                                <input type="range" class="ve__range" id="ve-stop-opacity"
                                    min="0" max="100" step="5" value="100" />
                                <span class="ve__range-val" id="ve-stop-opacity-val">100%</span>
                            </div>
                        </div>
                    </div>

                    <div class="ve__gradient-position-control" id="ve-gradient-position-wrap" hidden>
                        <label class="ve__field-label">{{ __('page-edit.gradient_position') }}</label>
                        <div class="ve__gradient-position-preview" id="ve-gradient-position-preview">
                            <div class="ve__gradient-position-handle" id="ve-gradient-handle" title="{{ __('page-edit.drag_to_position') }}"></div>
                        </div>
                    </div>

                    <div class="ve__gradient-angle-control" id="ve-gradient-angle-wrap" hidden>
                        <label class="ve__field-label">{{ __('page-edit.gradient_angle') }}</label>
                        <div class="ve__angle-dial-container">
                            <div class="ve__angle-dial" id="ve-angle-wheel">
                                <div class="ve__angle-dial-marker" id="ve-angle-indicator"></div>
                                <span class="ve__angle-dial-value" id="ve-angle-value">135°</span>
                            </div>
                            <input type="range" class="ve__range ve__angle-range" id="ve-gradient-angle"
                                min="0" max="360" step="1" value="135" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.bg_effects') }}</h4>
                <div class="ve__bg-effects">
                    @php $bgEffects = ['none', 'dots', 'grid', 'cross', 'diagonal', 'squares', 'mesh', 'emoji', 'noise']; @endphp
                    @foreach ($bgEffects as $effect)
                        <button type="button" class="ve__bg-effect{{ $effect === 'none' ? ' active' : '' }}"
                            data-bg-effect="{{ $effect }}" title="{{ __('page-edit.bg_effect_' . $effect) }}">
                            <span class="ve__bg-effect-preview {{ $effect }}-preview"></span>
                            <span class="ve__bg-effect-label">{{ __('page-edit.bg_effect_' . $effect) }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="ve__field" id="ve-effect-opacity-wrap" hidden>
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.effect_opacity') }}</label>
                        <span class="ve__range-val">0.1</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-effect-opacity" data-variable="--bg-effect-opacity"
                        min="0.02" max="0.3" step="0.02" value="0.1" />
                </div>

                <div class="ve__emoji-editor" id="ve-emoji-editor" hidden>
                    <label class="ve__field-label">{{ __('page-edit.emoji_preset') }}</label>
                    <div class="ve__emoji-presets" id="ve-emoji-presets">
                        <button type="button" class="ve__emoji-preset active" data-emoji-preset="stars" title="{{ __('page-edit.emoji_stars') }}">⭐</button>
                        <button type="button" class="ve__emoji-preset" data-emoji-preset="hearts" title="{{ __('page-edit.emoji_hearts') }}">❤️</button>
                        <button type="button" class="ve__emoji-preset" data-emoji-preset="fire" title="{{ __('page-edit.emoji_fire') }}">🔥</button>
                        <button type="button" class="ve__emoji-preset" data-emoji-preset="gaming" title="{{ __('page-edit.emoji_gaming') }}">🎮</button>
                        <button type="button" class="ve__emoji-preset" data-emoji-preset="nature" title="{{ __('page-edit.emoji_nature') }}">🌿</button>
                        <button type="button" class="ve__emoji-preset" data-emoji-preset="space" title="{{ __('page-edit.emoji_space') }}">🚀</button>
                        <button type="button" class="ve__emoji-preset" data-emoji-preset="custom" title="{{ __('page-edit.emoji_custom') }}">✏️</button>
                    </div>
                    <div class="ve__emoji-custom-wrap" id="ve-emoji-custom-wrap" hidden>
                        <label class="ve__field-label">{{ __('page-edit.emoji_custom_input') }}</label>
                        <input type="text" class="ve__emoji-input" id="ve-emoji-custom"
                            placeholder="⭐ ✨ 💫 🌟" value="⭐ ✨ 💫 🌟" maxlength="50" />
                    </div>
                    <div class="ve__field" style="margin-top: var(--space-sm);">
                        <div class="ve__field-row">
                            <label class="ve__field-label">{{ __('page-edit.emoji_angle') }}</label>
                            <span class="ve__range-val" id="ve-emoji-angle-val">0°</span>
                        </div>
                        <input type="range" class="ve__range" id="ve-emoji-angle" min="-45" max="45" step="5" value="0" />
                    </div>
                    <div class="ve__field">
                        <div class="ve__field-row">
                            <label class="ve__field-label">{{ __('page-edit.emoji_size') }}</label>
                            <span class="ve__range-val" id="ve-emoji-size-val">24px</span>
                        </div>
                        <input type="range" class="ve__range" id="ve-emoji-size" min="16" max="48" step="4" value="24" />
                    </div>
                    <div class="ve__field">
                        <div class="ve__field-row">
                            <label class="ve__field-label">{{ __('page-edit.emoji_spacing') }}</label>
                            <span class="ve__range-val" id="ve-emoji-spacing-val">64px</span>
                        </div>
                        <input type="range" class="ve__range" id="ve-emoji-spacing" min="32" max="128" step="8" value="64" />
                    </div>
                    <div class="ve__field ve__field--switch">
                        <label class="ve__field-label">{{ __('page-edit.emoji_use_accent') }}</label>
                        <x-fields.toggle name="ve-emoji-accent" id="ve-emoji-accent" :checked="true" />
                    </div>
                </div>
            </section>

            @can('admin.boss')
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.bg_image') }}</h4>
                <div class="ve__upload-row">
                    <div class="ve__upload-slot" data-upload-type="bg_image">
                        <label class="ve__upload-slot-inner">
                            <div class="ve__upload-slot-preview" id="ve-bg-image-preview">
                                @if(config('app.bg_image'))
                                    <img src="{{ asset(config('app.bg_image')) }}" alt="" />
                                @endif
                            </div>
                            <div class="ve__upload-slot-overlay"><x-icon path="ph.regular.camera" /></div>
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" hidden data-upload="bg_image" />
                        </label>
                        <div class="ve__upload-slot-footer">
                            <span class="ve__upload-slot-label">{{ __('page-edit.bg_image_dark') }}</span>
                            <button type="button" class="ve__upload-slot-delete" data-delete="bg_image" @if(!config('app.bg_image')) hidden @endif>
                                <x-icon path="ph.regular.x" />
                            </button>
                        </div>
                    </div>
                    <div class="ve__upload-slot" data-upload-type="bg_image_light">
                        <label class="ve__upload-slot-inner">
                            <div class="ve__upload-slot-preview" id="ve-bg-image-light-preview">
                                @if(config('app.bg_image_light'))
                                    <img src="{{ asset(config('app.bg_image_light')) }}" alt="" />
                                @endif
                            </div>
                            <div class="ve__upload-slot-overlay"><x-icon path="ph.regular.camera" /></div>
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" hidden data-upload="bg_image_light" />
                        </label>
                        <div class="ve__upload-slot-footer">
                            <span class="ve__upload-slot-label">{{ __('page-edit.bg_image_light_label') }}</span>
                            <button type="button" class="ve__upload-slot-delete" data-delete="bg_image_light" @if(!config('app.bg_image_light')) hidden @endif>
                                <x-icon path="ph.regular.x" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.site_logo') }}</h4>
                <div class="ve__upload-row">
                    <div class="ve__upload-slot ve__upload-slot--logo" data-upload-type="logo">
                        <label class="ve__upload-slot-inner">
                            <div class="ve__upload-slot-preview" id="ve-logo-preview">
                                @if(config('app.logo') && !str_ends_with(config('app.logo'), 'logo.svg'))
                                    <img src="{{ asset(config('app.logo')) }}" alt="" />
                                @endif
                            </div>
                            <div class="ve__upload-slot-overlay"><x-icon path="ph.regular.camera" /></div>
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" hidden data-upload="logo" />
                        </label>
                        <div class="ve__upload-slot-footer">
                            <span class="ve__upload-slot-label">{{ __('page-edit.logo_dark') }}</span>
                            <button type="button" class="ve__upload-slot-delete" data-delete="logo" @if(!config('app.logo') || str_ends_with(config('app.logo'), 'logo.svg')) hidden @endif>
                                <x-icon path="ph.regular.x" />
                            </button>
                        </div>
                    </div>
                    <div class="ve__upload-slot ve__upload-slot--logo" data-upload-type="logo_light">
                        <label class="ve__upload-slot-inner">
                            <div class="ve__upload-slot-preview" id="ve-logo-light-preview">
                                @if(config('app.logo_light') && !str_ends_with(config('app.logo_light'), 'logo-light.svg'))
                                    <img src="{{ asset(config('app.logo_light')) }}" alt="" />
                                @endif
                            </div>
                            <div class="ve__upload-slot-overlay"><x-icon path="ph.regular.camera" /></div>
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" hidden data-upload="logo_light" />
                        </label>
                        <div class="ve__upload-slot-footer">
                            <span class="ve__upload-slot-label">{{ __('page-edit.logo_light_label') }}</span>
                            <button type="button" class="ve__upload-slot-delete" data-delete="logo_light" @if(!config('app.logo_light') || str_ends_with(config('app.logo_light'), 'logo-light.svg')) hidden @endif>
                                <x-icon path="ph.regular.x" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            @endcan
        </div>

        {{-- ===== 5. Effects ===== --}}
        <div class="ve__panel" data-ve-panel="effects">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.glass_effects') }}</h4>
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.blur_amount') }}</label>
                        <span class="ve__range-val">10px</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-blur-amount" data-variable="--blur-amount"
                        min="0" max="32" step="2" value="10" data-unit="px" />
                </div>
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.card_opacity') }}</label>
                        <span class="ve__range-val">0.8</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-card-opacity" data-variable="--card-opacity"
                        min="0.4" max="1" step="0.05" value="0.8" />
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.animations') }}</h4>
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.transition_speed') }}</label>
                        <span class="ve__range-val">0.2s</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-transition" data-variable="--transition"
                        min="0.1" max="0.5" step="0.05" value="0.2" data-unit="s" />
                </div>
                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.hover_scale') }}</label>
                    <x-fields.toggle name="ve-hover-scale" id="ve-hover-scale" :checked="true" />
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.shadows') }}</h4>
                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.shadow_enabled') }}</label>
                    <x-fields.toggle name="ve-shadows" id="ve-shadows" :checked="true" />
                </div>
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.glow_intensity') }}</label>
                        <span class="ve__range-val">0</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-glow-intensity" data-variable="--glow-intensity"
                        min="0" max="1" step="0.1" value="0" />
                </div>
            </section>
        </div>
    </div>

    <footer class="ve__footer">
        <button type="button" class="ve__btn ve__btn--text" id="ve-reset">
            <x-icon path="ph.regular.arrow-counter-clockwise" />
            {{ __('page-edit.reset') }}
        </button>
        <div class="ve__footer-right">
            <button type="button" class="ve__btn ve__btn--secondary" id="ve-cancel">{{ __('def.cancel') }}</button>
            <button type="button" class="ve__btn ve__btn--primary" id="ve-save">{{ __('def.save') }}</button>
        </div>
    </footer>
</aside>
<div class="ve__backdrop" id="visual-editor-backdrop"></div>
