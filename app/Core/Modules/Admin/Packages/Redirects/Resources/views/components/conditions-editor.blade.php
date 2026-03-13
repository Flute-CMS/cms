@php
    $editorConfig = json_encode([
        'conditionTypes' => $conditionTypes,
        'operators' => $operators,
        'i18n' => [
            'typePlaceholder' => __('admin-redirects.fields.condition_type.placeholder'),
            'operatorPlaceholder' => __('admin-redirects.fields.condition_operator.placeholder'),
            'valuePlaceholder' => __('admin-redirects.fields.condition_value.placeholder'),
            'typeLabel' => __('admin-redirects.fields.condition_type.label'),
            'operatorLabel' => __('admin-redirects.fields.condition_operator.label'),
            'valueLabel' => __('admin-redirects.fields.condition_value.label'),
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

<div class="card mb-3 mt-3">
    <div class="card-header">
        <div>
            <span class="card-title">{{ __('admin-redirects.modal.conditions_title') }}</span>
            <small class="d-block text-muted mt-1">{{ __('admin-redirects.modal.conditions_help') }}</small>
        </div>
    </div>
    <div class="card-body" id="conditions-editor" data-config="{{ $editorConfig }}">
        @forelse ($groups as $g => $conditions)
            @if ($g > 0)
                <div class="ce-separator"><span class="ce-badge ce-or">OR</span></div>
            @endif
            <div class="ce-group" data-group="{{ $g }}">
                @foreach ($conditions as $c => $cond)
                    @if ($c > 0)
                        <div class="ce-connector"><span class="ce-badge ce-and">AND</span></div>
                    @endif
                    <div class="ce-row" data-condition="{{ $c }}">
                        <div class="ce-fields">
                            <div class="ce-field ce-field-type">
                                @if ($c === 0 && $g === 0)
                                    <label class="form-label">{{ __('admin-redirects.fields.condition_type.label') }}</label>
                                @endif
                                <div class="select-wrapper">
                                    <div class="select__field-container" data-controller="select"
                                        data-select-placeholder="{{ __('admin-redirects.fields.condition_type.placeholder') }}"
                                        data-select-allow-empty="1">
                                        <select name="conditions_{{ $g }}_{{ $c }}_type" class="select__field" data-select
                                            data-allow-empty="true" data-initial-value="{{ json_encode($cond['type'] ?? '') }}">
                                            <option value="" @if(empty($cond['type'])) selected @endif disabled>{{ __('admin-redirects.fields.condition_type.placeholder') }}</option>
                                            @foreach ($conditionTypes as $key => $label)
                                                <option value="{{ $key }}" @selected(($cond['type'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="ce-field ce-field-op">
                                @if ($c === 0 && $g === 0)
                                    <label class="form-label">{{ __('admin-redirects.fields.condition_operator.label') }}</label>
                                @endif
                                <div class="select-wrapper">
                                    <div class="select__field-container" data-controller="select"
                                        data-select-placeholder="{{ __('admin-redirects.fields.condition_operator.placeholder') }}"
                                        data-select-allow-empty="1">
                                        <select name="conditions_{{ $g }}_{{ $c }}_operator" class="select__field" data-select
                                            data-allow-empty="true" data-initial-value="{{ json_encode($cond['operator'] ?? '') }}">
                                            <option value="" @if(empty($cond['operator'])) selected @endif disabled>{{ __('admin-redirects.fields.condition_operator.placeholder') }}</option>
                                            @foreach ($operators as $key => $label)
                                                <option value="{{ $key }}" @selected(($cond['operator'] ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="ce-field ce-field-val">
                                @if ($c === 0 && $g === 0)
                                    <label class="form-label">{{ __('admin-redirects.fields.condition_value.label') }}</label>
                                @endif
                                <div class="input-wrapper">
                                    <div class="input__field-container">
                                        <input type="text" name="conditions_{{ $g }}_{{ $c }}_value" class="input__field"
                                            placeholder="{{ __('admin-redirects.fields.condition_value.placeholder') }}"
                                            value="{{ $cond['value'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ce-actions">
                            <button type="button" class="btn btn-outline-primary btn-tiny ce-btn-and" data-tooltip="AND">And</button>
                            <button type="button" class="btn btn-outline-warning btn-tiny ce-btn-or" data-tooltip="OR">Or</button>
                            <button type="button" class="btn btn-outline-error btn-tiny ce-btn-remove">
                                <x-icon path="ph.bold.x-bold" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="ce-empty">
                <button type="button" class="btn btn-outline-primary btn-small" id="ce-add-first">
                    <x-icon class="me-1" path="ph.bold.plus-bold" />
                    <span class="btn-label">{{ __('admin-redirects.buttons.add_condition') }}</span>
                </button>
            </div>
        @endforelse
    </div>
</div>
<input type="hidden" name="conditions_json" id="conditions-json-input" value="">