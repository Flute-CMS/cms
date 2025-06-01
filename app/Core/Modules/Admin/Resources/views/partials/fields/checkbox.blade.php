@component($typeForm, get_defined_vars())
    <x-fields.checkbox name="{{ $attributes['name'] }}" id="{{ $id }}"
        popover="{{ isset($popover) ? $popover : null }}"
        checked="{{ isset($attributes['value']) && $attributes['value'] && (!isset($attributes['checked']) || $attributes['checked'] !== false) }}">
        @if (isset($label))
            <x-slot:label>
                {{ $label }}
            </x-slot:label>
        @endif
    </x-fields.checkbox>
@endcomponent
