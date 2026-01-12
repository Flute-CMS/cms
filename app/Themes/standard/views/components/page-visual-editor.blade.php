<aside class="ve" id="visual-editor">
    {{-- Header --}}
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

    {{-- Segmented Control with Icons --}}
    <div class="ve__segments">
        <button type="button" class="ve__segment active" data-tab="colors" title="{{ __('page-edit.theme_colors') }}">
            <x-icon path="ph.regular.palette" />
        </button>
        <button type="button" class="ve__segment" data-tab="typography" title="{{ __('page-edit.typography') }}">
            <x-icon path="ph.regular.text-aa" />
        </button>
        <button type="button" class="ve__segment" data-tab="spacing" title="{{ __('page-edit.spacing') }}">
            <x-icon path="ph.regular.arrows-out-cardinal" />
        </button>
        <button type="button" class="ve__segment" data-tab="effects" title="{{ __('page-edit.effects') }}">
            <x-icon path="ph.regular.sparkle" />
        </button>
    </div>

    {{-- Content --}}
    <div class="ve__body">
        {{-- Colors Panel --}}
        <div class="ve__panel active" data-panel="colors">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.theme_colors') }}</h4>
                <div class="ve__colors">
                    @foreach(['accent', 'primary', 'secondary', 'background', 'text'] as $color)
                    <label class="ve__color" data-variable="--{{ $color }}">
                        <span class="ve__color-preview" style="background: var(--{{ $color }});"></span>
                        <input type="color" class="ve__color-input" />
                        <span class="ve__color-name">{{ __('page-edit.' . $color) }}</span>
                    </label>
                    @endforeach
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.background_style') }}</h4>
                <div class="ve__bg-types">
                    @php
                        $bgTypes = ['solid', 'linear-gradient', 'radial-gradient', 'mesh-gradient', 'subtle-gradient', 'aurora-gradient'];
                    @endphp
                    @foreach($bgTypes as $type)
                    <button type="button" class="ve__bg-type{{ $type === 'solid' ? ' active' : '' }}" data-bg-type="{{ $type }}">
                        <span class="ve__bg-type-preview {{ $type }}-preview"></span>
                    </button>
                    @endforeach
                </div>
                <div class="ve__gradient-row" id="ve-gradient-colors" hidden>
                    <span class="ve__gradient-label">{{ __('page-edit.theme_colors') }}</span>
                    <div class="ve__gradient-swatches">
                        @for($i = 1; $i <= 3; $i++)
                        <label class="ve__gradient-swatch">
                            <input type="color" id="ve-grad-{{ $i }}" data-variable="--bg-grad{{ $i }}" />
                        </label>
                        @endfor
                    </div>
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.border_radius') }}</h4>
                <div class="ve__row">
                    <div class="ve__radius-preview" id="ve-border-preview"></div>
                    <div class="ve__slider-wrap">
                        <input type="range" class="ve__range" id="ve-border-radius" data-variable="--border1" min="0" max="2" step="0.125" value="1" data-unit="rem" />
                        <span class="ve__range-val">1rem</span>
                    </div>
                </div>
            </section>
        </div>

        {{-- Typography Panel --}}
        <div class="ve__panel" data-panel="typography">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.fonts') }}</h4>
                
                <div class="ve__field">
                    <label class="ve__field-label">{{ __('page-edit.font_family') }}</label>
                    <div class="ve__select-wrap">
                        <select class="ve__select" id="ve-font-family" data-variable="--font">
                            @foreach(['Manrope', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Nunito', 'Raleway', 'Ubuntu', 'Rubik', 'Work Sans', 'DM Sans', 'Outfit', 'Plus Jakarta Sans', 'Space Grotesk', 'Lexend', 'Sora', 'Urbanist', 'Figtree'] as $font)
                            <option value="{{ $font }}">{{ $font }}</option>
                            @endforeach
                        </select>
                        <x-icon path="ph.regular.caret-down" class="ve__select-icon" />
                    </div>
                </div>

                <div class="ve__field">
                    <label class="ve__field-label">{{ __('page-edit.heading_font') }}</label>
                    <div class="ve__select-wrap">
                        <select class="ve__select" id="ve-heading-font" data-variable="--font-header">
                            <option value="inherit">{{ __('page-edit.same_as_body') }}</option>
                            @foreach(['Manrope', 'Inter', 'Roboto', 'Montserrat', 'Poppins', 'Raleway', 'Rubik', 'Work Sans', 'DM Sans', 'Playfair Display', 'Merriweather', 'Lora'] as $font)
                            <option value="{{ $font }}">{{ $font }}</option>
                            @endforeach
                        </select>
                        <x-icon path="ph.regular.caret-down" class="ve__select-icon" />
                    </div>
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.text_settings') }}</h4>
                
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.font_scale') }}</label>
                        <span class="ve__range-val" id="ve-font-scale-val">1.15</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-font-scale" data-variable="--font-scale" min="1.0" max="1.35" step="0.05" value="1.15" />
                </div>
            </section>

            <div class="ve__preview-card">
                <h3 class="ve__preview-h">{{ __('page-edit.preview_heading') }}</h3>
                <p class="ve__preview-p">{{ __('page-edit.preview_text') }}</p>
            </div>
        </div>

        {{-- Spacing Panel --}}
        <div class="ve__panel" data-panel="spacing">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.spacing') }}</h4>
                
                @php
                    $spaces = [
                        ['id' => 've-space-xs', 'var' => '--space-xs', 'label' => 'space_xs', 'min' => 0.25, 'max' => 1, 'step' => 0.125, 'default' => 0.5],
                        ['id' => 've-space-sm', 'var' => '--space-sm', 'label' => 'space_sm', 'min' => 0.5, 'max' => 1.5, 'step' => 0.125, 'default' => 0.75],
                        ['id' => 've-space-md', 'var' => '--space-md', 'label' => 'space_md', 'min' => 0.5, 'max' => 2, 'step' => 0.25, 'default' => 1],
                        ['id' => 've-space-lg', 'var' => '--space-lg', 'label' => 'space_lg', 'min' => 1, 'max' => 3, 'step' => 0.25, 'default' => 1.5],
                        ['id' => 've-space-xl', 'var' => '--space-xl', 'label' => 'space_xl', 'min' => 1.5, 'max' => 4, 'step' => 0.25, 'default' => 2],
                    ];
                @endphp

                @foreach($spaces as $space)
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.' . $space['label']) }}</label>
                        <span class="ve__range-val">{{ $space['default'] }}rem</span>
                    </div>
                    <input type="range" class="ve__range" id="{{ $space['id'] }}" data-variable="{{ $space['var'] }}" 
                           min="{{ $space['min'] }}" max="{{ $space['max'] }}" step="{{ $space['step'] }}" value="{{ $space['default'] }}" data-unit="rem" />
                </div>
                @endforeach
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.layout') }}</h4>
                
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.max_content_width') }}</label>
                        <span class="ve__range-val">1200px</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-max-width" data-variable="--max-content-width" min="960" max="1600" step="40" value="1200" data-unit="px" />
                </div>

                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.fullwidth_mode') }}</label>
                    <x-fields.toggle name="ve-fullwidth" id="ve-fullwidth" :checked="false" />
                </div>
            </section>
        </div>

        {{-- Effects Panel --}}
        <div class="ve__panel" data-panel="effects">
            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.effects') }}</h4>
                
                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.blur_amount') }}</label>
                        <span class="ve__range-val">10px</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-blur-amount" data-variable="--blur-amount" min="0" max="24" step="2" value="10" data-unit="px" />
                </div>

                <div class="ve__field">
                    <div class="ve__field-row">
                        <label class="ve__field-label">{{ __('page-edit.transition_speed') }}</label>
                        <span class="ve__range-val">0.2s</span>
                    </div>
                    <input type="range" class="ve__range" id="ve-transition" data-variable="--transition" min="0.1" max="0.5" step="0.05" value="0.2" data-unit="s" />
                </div>
            </section>

            <section class="ve__section">
                <h4 class="ve__section-title">{{ __('page-edit.shadows') }}</h4>
                
                <div class="ve__field ve__field--switch">
                    <label class="ve__field-label">{{ __('page-edit.shadow_enabled') }}</label>
                    <x-fields.toggle name="ve-shadows" id="ve-shadows" :checked="true" />
                </div>
            </section>
        </div>
    </div>

    {{-- Footer --}}
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
