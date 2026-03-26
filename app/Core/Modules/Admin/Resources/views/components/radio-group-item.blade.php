@props(['value', 'label', 'small' => null, 'icon' => null, 'checked' => false, 'disabled' => false])
@aware(['name', 'variant' => null])

<label {{ $attributes->merge(['class' => 'radio-item' . ($disabled ? ' radio-item--disabled' : '')]) }}>
    <input type="radio" class="radio-item__input" name="{{ $name }}" value="{{ $value }}"
        @checked($checked) @disabled($disabled)>
    <span class="radio-item__content">
        @isset($icon)
            <span class="radio-item__icon" aria-hidden="true">
                <x-icon path="{{ $icon }}" />
            </span>
        @endisset
        <span class="radio-item__text">
            @isset($label)
                <span class="radio-item__label">{!! $label !!}</span>
            @endisset
            @if ($small)
                <span class="radio-item__desc">{!! $small !!}</span>
            @endif
        </span>
    </span>
    <span class="radio-item__control" aria-hidden="true"></span>
</label>
