@if (is_callable($typeForm))
    {!! $typeForm(get_defined_vars()) !!}
@else
@component($typeForm, get_defined_vars() ?? [])
    <x-fields.buttongroup
        name="{{ $attributes->get('name') }}"
        :options="$options ?? []"
        :value="$value ?? null"
        :size="$size ?? 'medium'"
        :color="$color ?? 'primary'"
        :fullWidth="$fullWidth ?? false"
        :disabled="$disabled ?? false"
        :yoyo="$yoyo ?? false"
        {{ $attributes->except(['name', 'options', 'value', 'size', 'color', 'fullWidth']) }}
    />
@endcomponent
@endif
