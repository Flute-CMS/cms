@component($typeForm, get_defined_vars())
    <x-fields.checkbox name="{{ $attributes['name'] }}" id="{{ $id }}"
        :value="isset($attributes['value']) ? $attributes['value'] : 'on'"
        popover="{{ isset($popover) ? $popover : null }}"
        :checked="isset($attributes['checked']) ? (bool) $attributes['checked'] : (isset($attributes['value']) && $attributes['value'])">
        @if (isset($label))
            <x-slot:label>
                {{ $label }}
            </x-slot:label>
        @endif
    </x-fields.checkbox>
@endcomponent
