<div class="d-flex align-items-center gap-4">
    <h4 class="d-flex">
        <x-icon :path="$social->icon" />
    </h4>

    <div class="d-flex flex-column">
        {{ $social->key }}
        <small class="text-muted">#{{ $social->id }}</small>
    </div>
</div>