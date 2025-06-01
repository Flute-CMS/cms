@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : __('lk.title') }}
@endsection

@push('content')
    <div class="h-100 container">
        <section class="lk-container" aria-labelledby="lk-title">
            <article class="lk-content" hx-swap="morph:outerHTML">
                @fragment('lk-card')
                    @yoyo('payment-form')
                @endfragment
            </article>
        </section>
    </div>
@endpush
