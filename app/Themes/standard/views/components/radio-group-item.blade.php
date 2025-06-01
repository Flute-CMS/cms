@props(['value', 'label', 'small', 'icon', 'checked' => false])
@aware(['name'])

<label {{ $attributes->merge(['class' => 'radio-option']) }}>
    <input type="radio" class="radio-input" name="{{ $name }}" value="{{ $value }}"
        @checked($checked)>
    <div class="radio-content">
        @isset($icon)
            <span class="radio-icon" aria-hidden="true">
                <x-icon path="{{ $icon }}" />
            </span>
        @endisset

        <div class="radio-option-content">
            @isset($label)
                <h6 class="radio-text-label">
                    {!! $label !!}
                </h6>
            @endisset

            @isset($small)
                <small class="radio-text-small">
                    {!! $small !!}
                </small>
            @endisset
        </div>
    </div>
</label>
