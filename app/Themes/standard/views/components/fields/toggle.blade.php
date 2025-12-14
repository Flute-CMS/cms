@props(['name', 'id' => $name, 'checked' => false, 'label' => null, 'disabled' => false])

<label class="toggle-switch">
    @if ($label)
        <span class="toggle-switch-label-text" id="{{ $id }}-label">{{ $label }}</span>
    @endif
    <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
        @if ($checked) checked @endif @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'toggle-switch-input']) }}>
    <span class="toggle-switch-slider"></span>
</label>
