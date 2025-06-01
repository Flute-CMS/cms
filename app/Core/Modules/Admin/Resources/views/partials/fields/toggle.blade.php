@component($typeForm, get_defined_vars())
    <x-fields.toggle
        name="{{ $attributes->get('name') }}"
        label="{{ $label ?? '' }}"
        :checked="$checked ?? false"
        :disabled="$disabled ?? false"
        :sendTrueOrFalse="$sendTrueOrFalse ?? false"
        yesvalue="{{ $attributes['yesvalue'] ?? '1' }}"
        novalue="{{ $attributes['novalue'] ?? '0' }}"
        placeholder="{{ $attributes['placeholder'] ?? __('Toggle') }}"
        :yoyo="$attributes['yoyo'] ?? false"
        {{ $attributes->except(['name', 'placeholder'])->class(['additional-class' => true]) }}
    />
@endcomponent
