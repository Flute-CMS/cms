<div class="form-group">
    @isset($title)
        <label for="{{ $id }}" class="form-label">{{ $title }}
            @if (isset($attributes['required']) && $attributes['required'])
                <sup class="text-danger">*</sup>
            @endif

            <x-popover :content="$popover ?? ''" />
        </label>
    @endisset

    {{ $slot }}
</div>

@isset($hr)
    <hr />
@endisset
