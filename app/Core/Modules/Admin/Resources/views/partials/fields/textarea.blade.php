@if (is_callable($typeForm))
    {!! $typeForm(get_defined_vars()) !!}
@else
@component($typeForm, get_defined_vars() ?? [])
    <x-fields.textarea
        name="{{ $attributes->get('name') }}"
        label="{{ $label ?? '' }}"
        :value="$value"
        rows="{{ $attributes->get('rows', 5) }}"
        placeholder="{{ $attributes['placeholder'] ?? __('Enter your message') }}"
        :readOnly="$readOnly ?? false"
        :withoutBottom="$withoutBottom ?? false"
        {{ $attributes->except(['name', 'placeholder', 'rows'])->class(['additional-class' => true]) }}
    />
@endcomponent
@endif
