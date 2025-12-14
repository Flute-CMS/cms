@props(['name' => null, 'value' => '1', 'checked' => false, 'label' => ''])

<div class="checkbox-wrapper">
    <div class="checkbox__field-container">
        <input type="checkbox" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
            value="{{ $value }}" {{ $checked ? 'checked' : '' }}
            {{ $attributes->class(['checkbox__field'])->merge(['class' => 'checkbox__field']) }} />

        @if ($label)
            <label for="{{ $attributes->get('id', $name) }}" class="checkbox__label">
                {{ $label }}
            </label>
        @endif
    </div>

    @error($name)
        <span class="checkbox__error">{{ $message }}</span>
    @enderror
</div>
