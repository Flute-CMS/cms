<ol class="list-group m-0 p-0" data-sortable data-sortable-group="nested" data-sortable-handle=".reorder-handle">
    @foreach ($items as $item)
        <li class="sortable-list-item list-group-item" data-id="{{ $item->id }}">
            <div class="d-flex justify-content-between align-items-center reorder-handle px-3 py-3">
                <div class="d-flex align-items-center w-100">
                    @if (!empty($item->children))
                        <button type="button" class="toggle-children btn-reset me-2" aria-expanded="true" aria-label="Свернуть">
                            <span class="icon-expanded"><x-icon path="ph.regular.caret-down" /></span>
                            <span class="icon-collapsed"><x-icon path="ph.regular.caret-right" /></span>
                        </button>
                    @else
                        <span class="me-2" aria-hidden="true"></span>
                    @endif
                    <button type="button" class="move-btn js-move-up me-1" title="Переместить вверх" aria-label="Переместить вверх">
                        <x-icon path="ph.regular.arrow-up" />
                    </button>
                    <button type="button" class="move-btn js-move-down me-3" title="Переместить вниз" aria-label="Переместить вниз">
                        <x-icon path="ph.regular.arrow-down" />
                    </button>
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
                        data-sortable-handle=".reorder-handle"></ol>
                @endif
            @endif
        </li>
    @endforeach
</ol>
