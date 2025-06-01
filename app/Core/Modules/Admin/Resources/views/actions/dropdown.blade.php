@component($typeForm, get_defined_vars())
    @php
        $id = uniqid();
        $onlyIconClass = isset($icon) && empty($name) ? 'onlyIcon' : '';
    @endphp

    <button {{ $attributes->merge(['class' => $onlyIconClass]) }} type="button" aria-haspopup="true" aria-expanded="false"
        data-dropdown-open="{{ $id }}">
        @isset($icon)
            <x-icon :path="$icon" class="overflow-visible" />
        @endisset

        {{ $name ?? '' }}
    </button>

    <div data-dropdown="{{ $id }}" class="admin-dropdown">
        <div>
            @foreach ($list as $item)
                {!! $item->build($source) !!}
            @endforeach
        </div>
    </div>
@endcomponent
