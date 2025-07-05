<div>
    v{{ $module->installedVersion ?? '0.0.0' }}
    <small @class([
        $module->installedVersion < $module->version ? 'accent' : 'text-muted',
    ])>({{ $module->version ?? '0.0.0' }})
        @if ($module->installedVersion < $module->version)
            <x-popover content="Доступно обновление до {{ $module->version }}" />
        @endif
    </small>

    @include('admin-update::components.update-badge', [
        'type' => 'modules',
        'identifier' => $module->key,
    ])
</div>
