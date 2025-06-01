@empty(!$title)
    <fieldset>
        <div class="col mb-2 p-0">
            <legend class="text-black text-black">
                <h4>
                    {{ $title }}
                </h4>

                @if (!empty($description))
                    <small class="d-block text-muted mb-0 text-balance">
                        {!! __($description ?? '') !!}
                    </small>
                @endif
            </legend>
        </div>
    </fieldset>
@endempty

<div class="d-flex flex-between align-center flex-md-row flex-column mb-3 flex-row gap-3">
    @if ($searchable)
        @include('admin::partials.layouts.table-search')
    @endif

    @empty(!$commandBar)
        <div class="d-flex justify-content-end ms-auto gap-2">
            @foreach ($commandBar as $command)
                <div>
                    {!! $command !!}
                </div>
            @endforeach
        </div>
    @endempty
</div>

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
                            <div class="py-4 text-center">
                                @isset($iconNotFound)
                                    <h1 class="flex-center text-muted mb-1">
                                        <x-icon :path="$iconNotFound" />
                                    </h1>
                                @endisset
                                <h3>
                                    {!! $textNotFound !!}
                                </h3>
                                <p class="text-muted flex-center text-balance">
                                    {!! $subNotFound !!}
                                </p>
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
