@props([
    'name' => '',
    'checked' => false,
    'disabled' => false,
])

<label class="toggle-track">
    <input
        type="checkbox"
        name="{{ $name }}"
        @if ($checked) checked @endif
        @if ($disabled) disabled @endif
        {{ $attributes }}
    >
    <span class="toggle-thumb"></span>
</label>
