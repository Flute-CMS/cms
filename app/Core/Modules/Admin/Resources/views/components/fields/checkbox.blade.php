@props([
    'name' => null,
    'value' => 'on',
    'checked' => false,
    'label' => '',
    'popover' => '',
    'bare' => false,
    'compact' => false,
])

@if ($bare)
    <input type="checkbox" @if ($name) name="{{ $name }}" @endif value="{{ $value }}"
        {{ $checked ? 'checked' : '' }} {{ $attributes }} />
@else
    <div class="checkbox-wrapper @if ($compact) checkbox--compact @endif">
        <div class="checkbox__field-container">
            <input type="checkbox" name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" value="{{ $value }}"
                {{ $checked ? 'checked' : '' }} {{ $attributes->class(['checkbox__field'])->merge(['class' => 'checkbox__field']) }} />

            <label for="{{ $attributes->get('id', $name) }}" class="checkbox__label">
                @if ($label)
                    {{ $label }}
                    @if (isset($popover) && !empty($popover))
                        <x-popover :content="$popover" />
                    @endif
                @endif
            </label>
        </div>

        @error($name)
            <span class="checkbox__error">{{ $message }}</span>
        @enderror
    </div>
@endif
