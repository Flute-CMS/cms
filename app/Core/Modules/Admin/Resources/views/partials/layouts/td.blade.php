<td class="text-{{ $align }} @if (!$width) text-truncate @endif {{ $class }}"
    data-column="{{ $data_column ?? $slug }}" 
    colspan="{{ $colspan }}" 
    @if (isset($aria_hidden)) aria-hidden="{{ $aria_hidden }}" @endif
    @style([
        "min-width:$width;" => $width,
        "$style" => $style,
    ])>
    <div>
        @isset($render)
            {!! $value !!}
        @else
            {{ $value }}
        @endisset
    </div>
</td>
