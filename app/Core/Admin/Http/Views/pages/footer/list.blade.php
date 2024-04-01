@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.footer.header')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/footer.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.footer.header')</h2>
            <p>@t('admin.footer.description')</p>
        </div>
        <div>
            <a href="{{ url('admin/footer/add') }}" class="btn size-s outline">
                @t('admin.footer.add')
            </a>
        </div>
    </div>

    @if (sizeof(footer()->all()) > 0)
        <ul class="footer-group nested-sortable">
            @foreach (footer()->all() as $item)
                <li class="sortable-item sortable-dropdown draggable" data-id="{{ $item['id'] }}" id="{{ $item['id'] }}"
                    style="">
                    <div class="card-sortable">
                        <div class="card-body d-flex justify-content-between">
                            <span class="sortable-text">
                                <i class="ph ph-arrows-out-cardinal sortable-handle"></i>

                                <span class="badge">{{ $item['id'] }} - {{ $item['title'] }}</span>
                            </span>

                            <div class="sortable-buttons">
                                <a href="{{ url('admin/footer/edit/' . $item['id']) }}"class="change"
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

                                            <span class="badge">{{ $child['id'] }} - {{ $child['title'] }}</span>
                                        </span>

                                        <div class="sortable-buttons">
                                            <a href="{{ url('admin/footer/edit/' . $child['id']) }}"class="change"
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
        <div class="table_empty">
            @t('def.no_results_found')
        </div>
    @endif
@endpush

@push('footer')
    <script src="https://SortableJS.github.io/Sortable/Sortable.js"></script>
    @at('Core/Admin/Http/Views/assets/js/pages/footer/list.js')
@endpush
