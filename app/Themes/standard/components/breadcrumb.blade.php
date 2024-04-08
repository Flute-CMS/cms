@push('header')
    @at(tt('assets/styles/components/_breadcrumb.scss'))
@endpush

@if (breadcrumb()->all())
    <nav aria-label="breadcrumb" class="breadcrumb">
        <ul>
            @foreach (breadcrumb()->all() as $index => $crumb)
                <li>
                    @if ($crumb['url'])
                        <a class="bread__item" href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                    @else
                        <div class="bread__item">{{ $crumb['title'] }}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
