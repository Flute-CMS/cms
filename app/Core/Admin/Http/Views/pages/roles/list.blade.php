@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.roles.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/roles.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.roles.header')</h2>
            <p>@t('admin.roles.description')</p>
        </div>
        <div>
            <a href="{{ url('admin/roles/add') }}" class="btn size-s outline">
                @t('admin.roles.add')
            </a>
        </div>
    </div>

    @if (sizeof($roles) > 0)
        <ul id="roles" class="roles-group">
            @foreach ($roles as $role)
                <li class="sortable-item sortable-dropdown @if (($role->priority > $priority->priority || $role->id === $priority->id) && !user()->hasPermission('admin.boss')) non-draggable 
                @else 
                draggable @endif"
                    data-id="{{ $role->id }}" style="">
                    <div class="card-sortable">
                        <div class="card-body d-flex justify-content-between">
                            <span class="sortable-text">
                                @if (($role->priority <= $priority->priority && $role->id !== $priority->id) || user()->hasPermission('admin.boss'))
                                    <i class="ph ph-arrows-out-cardinal sortable-handle"></i>
                                @endif

                                <span class="badge"
                                    style="background-color: {{ $role->color }}">{{ $role->name }}</span>

                                <span class="text-body-secondary">
                                    (ID: {{ $role->id }}, @t('admin.roles.priority'): {{ $role->priority }})
                                </span>
                            </span>

                            @if (($role->priority <= $priority->priority && $role->id !== $priority->id) || user()->hasPermission('admin.boss'))
                                <div class="sortable-buttons">
                                    <a href="{{ url('admin/roles/edit/' . $role->id) }}"class="change"
                                        data-tooltip="@t('def.edit')" data-tooltip-conf="left">
                                        <i class="ph ph-pencil"></i>
                                    </a>
                                    <div class="delete" data-deleterole="{{ $role->id }}"
                                        data-tooltip="@t('def.delete')" data-tooltip-conf="left">
                                        <i class="ph ph-trash"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <button id="saveRoles" class="btn primary size-s mt-4">@t('def.save')</button>
    @else
        <div class="table_empty">
            @t('def.no_results_found')
        </div>
    @endif
@endpush

@push('footer')
    <script src="https://SortableJS.github.io/Sortable/Sortable.js"></script>
    <script>
        var adminRoleId = {{ $priority->id }};
        var adminRolePriority = {{ $priority->priority }};
    </script>
    @at('Core/Admin/Http/Views/assets/js/pages/roles/list.js')
@endpush
