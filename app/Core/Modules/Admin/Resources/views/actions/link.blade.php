@if (is_callable($typeForm))
    {!! $typeForm(get_defined_vars()) !!}
@else
@component($typeForm, get_defined_vars())
    <a
        data-turbo="{{ var_export($turbo) }}"
        {{ $attributes }}
    >
        @isset($icon)
            <x-orchid-icon :path="$icon" class="overflow-visible"/>
        @endisset

        {{ $name ?? '' }}
    </a>
@endcomponent
@endif
