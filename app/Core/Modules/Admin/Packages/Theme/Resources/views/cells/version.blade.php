<div>
    v{{ $theme->version }}

    @include('admin-update::components.update-badge', [
        'type' => 'themes',
        'identifier' => $theme->key,
    ])
</div>
