@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.header.register') }}
@endsection

@push('content')
    <div class="container">
        <section class="auth-container">
            <div class="auth-card">
                <header class="auth-header">
                    <h2>@t('auth.header.register')</h2>
                    <p>@t('auth.create_account_desc')</p>
                </header>

                <article>
                    @fragment('register-card')
                        @include('flute::partials.social-login')

                        @if (!empty(social()->getAll()))
                            <div class="auth-divider">@t('def.or')</div>
                        @endif

                        @yoyo('register')
                    @endfragment
                </article>

                <footer class="auth-footer">
                    <p>@t('auth.have_account')
                        <x-link hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                                href="{{ url('/login') }}">@t('auth.login')</x-link>
                    </p>
                </footer>
            </div>
        </section>
    </div>
@endpush
