@push('admin-main::charts')
    <div class="row gy-4">
        @foreach (app(Flute\Core\Admin\Builders\AdminMainBuilder::class)->all() as $chart)
            <div class="col-md-{{ $chart['col-md'] }}">
                <div class="card p-0 pt-3 skeleton">
                    {!! $chart['class']->container() !!}
                </div>
            </div>
        @endforeach
    </div>
@endpush

@foreach (app(Flute\Core\Admin\Builders\AdminMainBuilder::class)->all() as $chart)
    @push('footer')
        {!! $chart['class']->script() !!}
    @endpush
@endforeach