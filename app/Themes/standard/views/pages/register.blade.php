@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.header.register') }}
@endsection

@push('content')
    <div class="h-100 container mt-4">
        <section class="auth-container">
            <header class="auth-header">
                <h2>@t('auth.header.register')</h2>
                <p>@t('auth.have_account') <x-link hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                        href="{{ url('/login') }}" type="accent">@t('auth.login')</x-link></p>
            </header>

            <article>
                @fragment('register-card')
                    @include('flute::partials.social-login')

                    @yoyo('register')
                @endfragment
            </article>
        </section>
    </div>
@endpush
