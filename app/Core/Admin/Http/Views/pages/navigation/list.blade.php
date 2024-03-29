@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.navigation.header')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/navigation.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.navigation.header')</h2>
            <p>@t('admin.navigation.description')</p>
        </div>
        <div>
            <a href="{{ url('admin/navigation/add') }}" class="btn size-s outline">
                @t('admin.navigation.add')
            </a>
        </div>
    </div>

    @if (sizeof(navbar()->all()) > 0)
        <ul class="navigation-group nested-sortable">
            @foreach (navbar()->all() as $item)
                <li class="sortable-item sortable-dropdown draggable" data-id="{{ $item['id'] }}" id="{{ $item['id'] }}"
                    style="">
                    <div class="card-sortable">
                        <div class="card-body d-flex justify-content-between">
                            <span class="sortable-text">
                                <i class="ph ph-arrows-out-cardinal sortable-handle"></i>

                                @if ($item['icon'])
                                    <i class="item-icon ph {!! $item['icon'] !!}"></i>
                                @endif
                                <span class="badge">{{ $item['id'] }} - {{ $item['title'] }}</span>
                            </span>

                            <div class="sortable-buttons">
                                <a href="{{ url('admin/navigation/edit/' . $item['id']) }}"class="change"
                                    data-tooltip="@t('def.edit')" data-tooltip-conf="left">
                                    <i class="ph ph-pencil"></i>
                                </a>
                                <div class="delete" data-deleteitem="{{ $item['id'] }}" data-tooltip="@t('def.delete')"
                                    data-tooltip-conf="left">
                                    <i class="ph ph-trash"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="list-group nested-sortable" id="{{ $item['id'] }}">
                        @foreach ($item['children'] as $child)
                            <li class="sortable-item sortable-dropdown draggable" data-id="{{ $child['id'] }}"
                                id="{{ $child['id'] }}" style="">
                                <div class="card-sortable">
                                    <div class="card-body d-flex justify-content-between">
                                        <span class="sortable-text">
                                            <i class="ph ph-arrows-out-cardinal sortable-handle"></i>

                                            @if ($child['icon'])
                                                <i class="item-icon ph {!! $child['icon'] !!}"></i>
                                            @endif

                                            <span class="badge">{{ $child['id'] }} - {{ $child['title'] }}</span>
                                        </span>

                                        <div class="sortable-buttons">
                                            <a href="{{ url('admin/navigation/edit/' . $child['id']) }}"class="change"
                                                data-tooltip="@t('def.edit')" data-tooltip-conf="left">
                                                <i class="ph ph-pencil"></i>
                                            </a>
                                            <div class="delete" data-deleteitem="{{ $child['id'] }}"
                                                data-tooltip="@t('def.delete')" data-tooltip-conf="left">
                                                <i class="ph ph-trash"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>

        <button id="save" class="btn primary size-s mt-4">@t('def.save')</button>
    @else
        <div class="navigation_empty">
            @t('admin.navigation.empty')
        </div>
    @endif
@endpush

@push('footer')
    <script src="http://SortableJS.github.io/Sortable/Sortable.js"></script>
    @at('Core/Admin/Http/Views/assets/js/pages/navigation/list.js')
@endpush
