@props(['name', 'value' => 'on', 'checked' => false, 'label' => '', 'popover' => ''])

<div class="checkbox-wrapper">
    <div class="checkbox__field-container">
        <input type="checkbox" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $attributes->class(['checkbox__field'])->merge(['class' => 'checkbox__field']) }} />

        <label for="{{ $attributes->get('id', $name) }}" class="checkbox__label">
            @if ($label)
                {{ $label }}

                @if (isset($popover) && ! empty($popover))
                    <x-popover :content="$popover" />
                @endif
            @endif
        </label>
    </div>

    @error($name)
        <span class="checkbox__error">{{ $message }}</span>
    @enderror
</div>