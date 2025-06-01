<div class="form-group mb-0">
    @isset($title)
        <label for="{{ $id }}" class="form-label mb-0">
            {{ $title }}
        </label>
    @endisset

    {{ $slot }}
</div>
