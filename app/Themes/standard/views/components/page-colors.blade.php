<div class="color-picker-panel" id="page-colors-panel" aria-label="{{ __('page-edit.background_style') }}">
    <div class="panel-header">
        <div class="toolbar functional">
            <div class="background-control bg-trigger-wrap" id="background-control">
                <div class="bg-current" id="bg-current">
                    <div class="bg-swatch" id="bg-swatch" data-tooltip="{{ __('page-edit.background_style') }}"></div>
                    <div class="gradient-colors" id="gradient-colors">
                        <div class="grad-color" data-index="1">
                            <input type="color" class="grad-input" id="grad-color-1" title="{{ __('page-edit.linear') }} A" />
                        </div>
                        <div class="grad-color" data-index="2">
                            <input type="color" class="grad-input" id="grad-color-2" title="{{ __('page-edit.linear') }} B" />
                        </div>
                        <div class="grad-color" data-index="3">
                            <input type="color" class="grad-input" id="grad-color-3" title="{{ __('page-edit.linear') }} C" />
                        </div>
                    </div>
                </div>
                <div class="gradient-overlay" id="gradient-overlay" aria-hidden="true">
                    <div class="gradient-variants">
                        <div class="gradient-variant" data-type="solid" title="{{ __('page-edit.solid') }}">
                            <div class="variant-preview variant-solid"></div>
                        </div>
                        <div class="gradient-variant" data-type="linear-gradient" title="{{ __('page-edit.linear') }}">
                            <div class="variant-preview variant-linear"></div>
                        </div>
                        <div class="gradient-variant" data-type="radial-gradient" title="{{ __('page-edit.radial') }}">
                            <div class="variant-preview variant-radial"></div>
                        </div>
                        <div class="gradient-variant" data-type="mesh-gradient" title="{{ __('page-edit.mesh') }}">
                            <div class="variant-preview variant-mesh"></div>
                        </div>
                        <div class="gradient-variant" data-type="subtle-gradient" title="{{ __('page-edit.subtle') }}">
                            <div class="variant-preview variant-subtle"></div>
                        </div>
                        <div class="gradient-variant" data-type="aurora-gradient" title="{{ __('page-edit.aurora') }}">
                            <div class="variant-preview variant-aurora"></div>
                        </div>
                        <div class="gradient-variant" data-type="sunset-gradient" title="{{ __('page-edit.sunset') }}">
                            <div class="variant-preview variant-sunset"></div>
                        </div>
                        <div class="gradient-variant" data-type="ocean-gradient" title="{{ __('page-edit.ocean') }}">
                            <div class="variant-preview variant-ocean"></div>
                        </div>
                        <div class="gradient-variant" data-type="spotlight-gradient" title="{{ __('page-edit.spotlight') }}">
                            <div class="variant-preview variant-spotlight"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-width-toggle" id="container-width-toggle" data-tooltip="{{ __('page-edit.container_width_toggle') }}">
                <input type="checkbox" id="container-width-checkbox" class="toggle-input">
                <label for="container-width-checkbox" class="toggle-label">
                    <div class="toggle-track">
                        <div class="toggle-thumb">
                            <div class="toggle-icon toggle-icon-container">
                                <x-icon path="ph.regular.frame-corners" />
                            </div>
                            <div class="toggle-icon toggle-icon-fullwidth">
                                <x-icon path="ph.regular.arrows-horizontal" />
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            <div class="history-controls">
                <x-button type="outline-primary" size="small" id="undo-button" disabled>
                    <x-icon path="ph.regular.arrow-bend-up-left" />
                </x-button>
                <x-button type="outline-primary" size="small" id="redo-button" disabled>
                    <x-icon path="ph.regular.arrow-bend-up-right" />
                </x-button>
                <x-button type="outline-primary" size="small" id="border-editor-btn" data-tooltip="{{ __('page-edit.border_radius') }}">
                    <x-icon path="ph.regular.squares-four" />
                </x-button>
                <x-button type="outline-error" size="small" id="reset-colors-button">
                    <x-icon path="ph.regular.arrow-counter-clockwise" />
                </x-button>
            </div>
        </div>

    </div>

    <div class="color-picker-blocks">
        <div class="color-block" data-variable="--accent">
            <div class="color-display" style="background-color: var(--accent);">
                <p>{{ __('page-edit.accent') }}</p>
                <input type="color" class="color-input" title="{{ __('page-edit.select_color') }}" />
            </div>
            <span class="contrast-rating" data-tooltip="{{ __('page-edit.contrast_rating') }}"></span>
        </div>
        <div class="color-block" data-variable="--primary">
            <div class="color-display" style="background-color: var(--primary);">
                <p>{{ __('page-edit.primary') }}</p>
                <input type="color" class="color-input" title="{{ __('page-edit.select_color') }}" />
            </div>
            <span class="contrast-rating" data-tooltip="{{ __('page-edit.contrast_rating') }}"></span>
        </div>
        <div class="color-block" data-variable="--secondary">
            <div class="color-display" style="background-color: var(--secondary);">
                <p>{{ __('page-edit.secondary') }}</p>
                <input type="color" class="color-input" title="{{ __('page-edit.select_color') }}" />
            </div>
            <span class="contrast-rating" data-tooltip="{{ __('page-edit.contrast_rating') }}"></span>
        </div>
        <div class="color-block" data-variable="--background">
            <div class="color-display" style="background-color: var(--background);">
                <p>{{ __('page-edit.background') }}</p>
                <input type="color" class="color-input" title="{{ __('page-edit.select_color') }}" />
            </div>
            <span class="contrast-rating" data-tooltip="{{ __('page-edit.contrast_rating') }}"></span>
        </div>
        <div class="color-block" data-variable="--text">
            <div class="color-display" style="background-color: var(--text);">
                <p>{{ __('page-edit.text') }}</p>
                <input type="color" class="color-input" title="{{ __('page-edit.select_color') }}" />
            </div>
            <span class="contrast-rating" data-tooltip="{{ __('page-edit.contrast_rating') }}"></span>
        </div>
        
    </div>

    <div class="color-picker-buttons">
        <div class="buttons-right">
            <x-button type="outline-primary" size="medium" id="page-colors-cancel">
                {{ __('def.cancel') }}
            </x-button>
            <x-button type="primary" size="medium" id="save-colors-button" hx-post="{{ url('api/pages/save-colors') }}"
            hx-vals="js:{colors: parseCurrentThemeColors(), theme: document.documentElement.getAttribute('data-theme'), containerWidth: getContainerWidthMode()}"
            hx-swap="none">
                <x-icon path="ph.regular.check-circle" />
                {{ __('def.save') }}
            </x-button>
        </div>
    </div>
</div>

<div class="border-editor-panel" id="border-editor-panel" aria-label="{{ __('page-edit.border_radius') }}">
    <div class="border-editor-content">
        <div class="range-preview">
            <div class="preview-box"></div>
        </div>
        <div class="range-control">
            <input type="range" id="border-input" class="range-input" min="0.25" max="4" step="0.25" value="1" />
            <span class="range-value">1rem</span>
        </div>
        <div class="border-editor-buttons">
            <x-button type="outline-primary" size="medium" id="border-editor-cancel">
                {{ __('def.cancel') }}
            </x-button>
            <x-button type="primary" size="medium" id="border-editor-save">
                {{ __('def.save') }}
            </x-button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="@asset('assets/js/libs/pickr.js')"></script>
@endpush
