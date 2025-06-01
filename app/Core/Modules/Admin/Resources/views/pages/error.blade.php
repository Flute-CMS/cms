@extends('admin::app')

@section('title')
    {{ $code . ' - ' . $message }}
@endsection

@push('content')
    @includeWhen(request()->isBoost(), 'admin::partials.breadcrumb')

    <section id="page-error-container">
        @if ($code == 404)
            <div class="not-found-image">
                @at(tt('assets/images/not_found.svg'))
            </div>
        @endif
        
        <div>
            <h1>{{ $code }}</h1>
            <p>{{ $message }}</p>
        </div>
    </section>
@endpush
