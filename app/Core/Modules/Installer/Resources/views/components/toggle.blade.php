@props([
    'name' => '',
    'checked' => false,
    'disabled' => false,
    'class' => '',
])

<label class="toggle-switch {{ $class }}">
    <input
        type="checkbox"
        name="{{ $name }}"
        class="toggle-switch__input"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
    >
    <span class="toggle-switch__slider"></span>
</label> 