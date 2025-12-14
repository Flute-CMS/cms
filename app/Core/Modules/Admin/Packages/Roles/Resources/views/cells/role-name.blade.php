<div class="d-flex items-center gap-3">
    <span class="role-name" title="{{ __('admin-roles.table.role_name') }}" data-tooltip="Priority: {{ $role->priority }}">
        <span class="role-color" style="background-color: {{ $role->color }}"></span>
        {{ $role->name }}

        @if($role->icon)
            <x-icon class="role-icon" path="{{ $role->icon }}"></x-icon>
        @endif
    </span>
    <small class="badge warning role-id cursor-pointer" title="{{ __('admin-roles.table.id') }}" data-copy="{{ $role->id }}"
        data-tooltip="{{ __('def.copy') }}" onclick="notyf.success('{{ __('def.copied') }}')">
        ID: {{ $role->id }}
    </small>
</div>