<div class="color-picker-panel" id="page-colors-panel">
    <div class="background-type-selector">
        <div class="background-options">
            <div class="background-option" data-type="solid" title="{{ __('page-edit.solid') }}">
                <div class="background-preview solid-preview"></div>
            </div>
            <div class="background-option" data-type="linear-gradient" title="{{ __('page-edit.linear') }}">
                <div class="background-preview linear-preview"></div>
            </div>
            <div class="background-option" data-type="radial-gradient" title="{{ __('page-edit.radial') }}">
                <div class="background-preview radial-preview"></div>
            </div>
            <div class="background-option" data-type="mesh-gradient" title="{{ __('page-edit.mesh') }}">
                <div class="background-preview mesh-preview"></div>
            </div>
            <div class="background-option" data-type="subtle-gradient" title="{{ __('page-edit.subtle') }}">
                <div class="background-preview subtle-preview"></div>
            </div>
            <div class="background-option" data-type="aurora-gradient" title="{{ __('page-edit.aurora') }}">
                <div class="background-preview aurora-preview"></div>
            </div>
            <div class="background-option" data-type="sunset-gradient" title="{{ __('page-edit.sunset') }}">
                <div class="background-preview sunset-preview"></div>
            </div>
            <div class="background-option" data-type="ocean-gradient" title="{{ __('page-edit.ocean') }}">
                <div class="background-preview ocean-preview"></div>
            </div>
            <div class="background-option" data-type="spotlight-gradient" title="{{ __('page-edit.spotlight') }}">
                <div class="background-preview spotlight-preview"></div>
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
        <div class="color-block" data-variable="--border1">
            <div class="color-display border-display">
                <p>{{ __('page-edit.border_radius') }}</p>
            </div>
        </div>
    </div>

    <div class="color-picker-controls">
        <x-button type="outline-primary" size="small" id="undo-button" disabled>
            <x-icon path="ph.regular.arrow-bend-up-left" />
        </x-button>
        <x-button type="outline-primary" size="small" id="redo-button" disabled>
            <x-icon path="ph.regular.arrow-bend-up-right" />
        </x-button>
    </div>

    <div class="color-picker-buttons">
        <x-button type="outline-primary" size="medium" id="page-colors-cancel">
            <x-icon path="ph.regular.arrow-left" />
            {{ __('def.cancel') }}
        </x-button>
        <x-button type="danger" size="medium" id="reset-colors-button" style="display: none;">
            <x-icon path="ph.regular.arrow-counter-clockwise" />
            {{ __('page-edit.reset') }}
        </x-button>
        <x-button type="primary" size="medium" id="save-colors-button" hx-post="{{ url('api/pages/save-colors') }}"
            hx-vals="js:{colors: parseCurrentThemeColors(), theme: document.documentElement.getAttribute('data-theme')}"
            hx-swap="none">
            <x-icon path="ph.regular.check-circle" />
            {{ __('def.save') }}
        </x-button>
    </div>
</div>

<div class="border-editor-panel" id="border-editor-panel">
    <div class="border-editor-content">
        <h3>{{ __('page-edit.border_radius') }}</h3>
        <div class="range-preview">
            <div class="preview-box"></div>
        </div>
        <div class="range-control">
            <input type="range" id="border-input" class="range-input" min="0.25" max="4"
                step="0.25" value="1" />
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
