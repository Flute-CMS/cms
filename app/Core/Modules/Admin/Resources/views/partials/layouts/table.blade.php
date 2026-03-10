@empty(!$title)
    <div class="mb-3">
        <h4 style="margin: 0; font-weight: 600; font-size: 1.125rem">{{ $title }}</h4>
        @if (!empty($description))
            <p class="text-muted mb-0" style="font-size: 0.8125rem; margin-top: 0.25rem">
                {!! __($description ?? '') !!}
            </p>
        @endif
    </div>
@endempty

<div class="d-flex flex-between align-center flex-md-row flex-column mb-3 flex-row gap-3">
    @if ($searchable)
        @include('admin::partials.layouts.table-search')
    @endif

    <div class="d-flex justify-content-end ms-auto gap-2">
        @if ($exportable ?? false)
            <button type="button" class="btn btn-outline-primary" data-dropdown-open="export-{{ $tableId }}">
                <x-icon path="ph.regular.export" />
                <span>{{ __('admin.export.title') }}</span>
                <x-icon path="ph.regular.caret-down" class="ms-1" />
            </button>
            <div data-dropdown="export-{{ $tableId }}">
                <div>
                    <a href="{{ url()->withGet()->addParams(['export' => 'csv', 'table' => $tableId])->get() }}"
                       class="dropdown-item">
                        <x-icon path="ph.regular.file-csv" />
                        {{ __('admin.export.csv') }}
                    </a>
                    <a href="{{ url()->withGet()->addParams(['export' => 'excel', 'table' => $tableId])->get() }}"
                       class="dropdown-item">
                        <x-icon path="ph.regular.file-xls" />
                        {{ __('admin.export.excel') }}
                    </a>
                </div>
            </div>
        @endif

        @foreach ($commandBar ?? [] as $command)
            <div>
                {!! $command !!}
            </div>
        @endforeach
    </div>
</div>

@empty(!$bulkBar)
    <div class="bulk-actions-floating" id="bulk-actions-{{ $tableId ?? '' }}" data-bulk-table="{{ $tableId ?? '' }}" style="display:none">
        <div class="bulk-actions-inner">
            <div class="bulk-chip">
                <span>{{ __('admin.bulk.selected') }}:</span>
                <strong class="bulk-selected-count">0</strong>
            </div>

            <div class="bulk-actions-scroll">
                @foreach ($bulkBar as $action)
                    {!! $action !!}
                @endforeach
            </div>

            <button type="button" class="btn btn-outline-primary btn-small bulk-clear-btn"
                aria-label="{{ __('admin.bulk.clear_selection') }}"
                data-tooltip="{{ __('admin.bulk.clear_selection') }}"
                data-table-id="{{ $tableId ?? '' }}">
                <x-icon path="ph.regular.x" />
            </button>
        </div>
    </div>
@endempty

<article class="card mb-3 table-card" hx-swap="outerHTML">
    <div class="table-responsive">
        <table @class([
            'table',
            'table-compact' => $compact,
            'table-bordered' => $bordered,
            'table-hover' => $hoverable,
        ]) id="{{ $tableId ?? '' }}">
            @if ($showHeader)
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            {!! $column->buildTh() !!}
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody>
                @forelse ($rows as $source)
                    <tr>
                        @foreach ($columns as $column)
                            {!! $column->buildTd($source, $loop->parent) !!}
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}">
                            <div class="table-empty-state">
                                @isset($iconNotFound)
                                    <div class="table-empty-state__icon">
                                        <x-icon :path="$iconNotFound" />
                                    </div>
                                @endisset
                                <h3 class="table-empty-state__title">
                                    {!! $textNotFound !!}
                                </h3>
                                @if (!empty($subNotFound))
                                    <p class="table-empty-state__sub">
                                        {!! $subNotFound !!}
                                    </p>
                                @endif
                                @if ($buttonNotFound)
                                    <div class="mt-2">
                                        {!! $buttonNotFound !!}
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse

                @if ($total->isNotEmpty() && !empty($rows))
                    <tr>
                        @foreach ($total as $column)
                            {!! $column->buildTd($repository, $loop) !!}
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if ($rows->isNotEmpty())
        @include('admin::partials.layouts.pagination', [
            'paginator' => $paginator,
            'columns' => $columns,
            'compact' => $compact,
        ])
    @endif
</article>
