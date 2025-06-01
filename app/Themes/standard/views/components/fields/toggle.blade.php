@props(['name', 'id' => $name, 'checked' => false, 'label' => null, 'disabled' => false])

<label class="toggle-switch">
    @if ($label)
        <x-forms.label for="{{ $name }}" class="toggle-switch-label-text">{{ $label }}</x-forms.label>
    @endif
    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
        @if ($checked) checked @endif @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'toggle-switch-input']) }}>
    <span class="toggle-switch-slider"></span>
</label>
