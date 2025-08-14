@if ($title || $description || !empty($commands))
    <legend class="d-flex flex-between my-3 flex-row gap-3">
        <div>
            <h4>{{ __($title ?? '') }} 
                @if ($popover)
                    <x-popover :content="$popover" />
                @endif
            </h4>

            @if (!empty($description))
                <small class="d-block text-muted text-balance mb-0">
                    {!! __($description ?? '') !!}
                </small>
            @endif
        </div>
        <ul class="row g-2 p-0">
            @foreach ($commands as $command)
                <li class="col-md">
                    {!! $command !!}
                </li>
            @endforeach
        </ul>
    </legend>
@endif

<div class="sortable-container mb-3 rounded" data-container-key="{{$slug}}:sortable"
    {{ $onSortEnd ? "yoyo:post=$onSortEnd" : '' }}
    hx-swap="outerHTML" hx-vals='js:{sortableResult: event.detail.sortable}' yoyo:trigger='sortEnd'>
    @if ($rows->isNotEmpty())
        <div class="sortable-toolbar d-flex justify-content-end gap-2 px-2 py-2">
            <button type="button" class="btn-link js-collapse-all" title="Свернуть все">
                <x-icon path="ph.regular.arrows-in-line-vertical" />
            </button>
            <button type="button" class="btn-link js-expand-all" title="Развернуть все">
                <x-icon path="ph.regular.arrows-out-line-vertical" />
            </button>
        </div>

        @include('admin::partials.sortable-list', [
            'items' => $rows,
            'columns' => $columns,
            'showBlockHeaders' => $showBlockHeaders,
        ])
    @else
        <div
            class="d-md-flex align-items-center flex-column px-md-0 w-100 text-md-start justify-content-center px-2 pb-5 pt-4 text-center">
            @isset($iconNotFound)
                <h1 class="d-flex text-muted mb-2">
                    <x-icon :path="$iconNotFound" />
                </h1>
            @endisset

            <div>
                <h3 class="fw-light">
                    {!! $textNotFound !!}
                </h3>

                {!! $subNotFound !!}
            </div>
        </div>
    @endif
</div>
