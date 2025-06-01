<ol class="list-group m-0 p-0" data-sortable data-sortable-group="nested" data-sortable-handle=".reorder-icon">
    @foreach ($items as $item)
        <li class="sortable-list-item list-group-item" data-id="{{ $item->id }}">
            <div class="d-flex justify-content-between align-items-center reorder-handle px-4 py-3">
                <div class="d-flex align-items-center w-100">
                    <x-icon path="ph.regular.arrows-out-cardinal" class="reorder-icon me-4 cursor-move" />
                    @foreach ($columns as $column)
                        <div class="{{ $loop->first ? 'me-auto' : 'ms-3' }}">
                            @if ($showBlockHeaders)
                                <div class="text-muted fw-normal">
                                    {!! $column->buildDt($item) !!}
                                </div>
                            @endif
                            {!! $column->buildDd($item) !!}
                        </div>
                    @endforeach
                </div>
            </div>

            @if (isset($item->children))
                @if ($item->children)
                    @include('admin::partials.sortable-list', [
                        'items' => $item->children,
                        'columns' => $columns,
                        'showBlockHeaders' => $showBlockHeaders,
                    ])
                @else
                    <ol class="list-group m-0 p-0" data-sortable data-sortable-group="nested"
                        data-sortable-handle=".reorder-icon"></ol>
                @endif
            @endif
        </li>
    @endforeach
</ol>
