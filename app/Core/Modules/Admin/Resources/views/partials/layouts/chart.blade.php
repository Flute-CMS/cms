<x-card class="my-3 rounded">
    @if (!empty($title) || !empty($description))
        <div class="col">
            <legend>
                <h5>{{ __($title ?? '') }} @if ($popover)
                        <x-popover :content="$popover" />
                    @endif
                </h5>

                @if (!empty($description))
                    <small class="d-block text-muted mb-1 text-balance">
                        {!! __($description ?? '') !!}
                    </small>
                @endif

            @empty(!$commandBar)
                <div class="d-flex justify-content-end gap-2 px-4 py-3">
                    @foreach ($commandBar as $command)
                        <div>
                            {!! $command !!}
                        </div>
                    @endforeach
                </div>
            @endempty
        </legend>
    </div>
@endif

<div class="position-relative w-100">
    @if (!empty($chart->labels()))
        {!! $chart->container() !!}
    @else
        <div class="mt-3 d-flex justify-content-center align-items-center flex-column">
            <h1 style="line-height: 1.3"><x-icon path="ph.regular.chart-line" class="icon-container text-muted" /></h1>
            <p class="text-muted">No data available</p>
        </div>
    @endif
</div>
@if (!empty($chart->labels()))
    {!! $chart->script() !!}
@endif
</x-card>
