@props(['name', 'id' => $name, 'checked' => false, 'label' => null, 'disabled' => false, 'yoyo' => false])

<label class="toggle-switch">
    @if ($label)
        <x-forms.label for="{{ $name }}" class="toggle-switch-label-text">{{ $label }}</x-forms.label>
    @endif

    @if ($yoyo)
        <input type="hidden" name="{{ $name }}_default" value="false" yoyo>
    @else
        <input type="hidden" name="{{ $name }}" value="false">
    @endif

    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="true" 
        @if ($checked) checked @endif 
        @if ($disabled) disabled @endif 
        {{ $attributes->merge(['class' => 'toggle-switch-input']) }} 
        @if ($yoyo) 
            yoyo 
        @endif>
    <span class="toggle-switch-slider"></span>
</label>