@props(['name', 'legend' => null])

<fieldset class="radio-group" {{ $attributes }}>
    @if ($legend)
        <legend>{{ $legend }}</legend>
    @endif
    {{ $slot }}
</fieldset>
