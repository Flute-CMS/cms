@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.header.reset') }}
@endsection

@push('content')
    <div class="h-100 container mt-4">
        <section class="auth-container">
            <header class="auth-header">
                <h2>@t('auth.header.reset')</h2>
            </header>

            <article>
                @fragment('reset-token-card')
                    @yoyo('reset-token', ['token' => $token])
                @endfragment
            </article>
        </section>
    </div>
@endpush
