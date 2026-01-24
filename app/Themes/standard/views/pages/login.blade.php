@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.header.login') }}
@endsection

@php
    $allowStandardAuth =
        !config('auth.only_social', false) || (config('auth.only_social', false) && sizeof(social()->getAll()) === 0);
@endphp

@push('content')
    <div class="container">
        <section class="auth-container">
            <div class="auth-card">
                <header class="auth-header">
                    <h2>@t('auth.header.login')</h2>
                    <p>@t('auth.welcome_back')</p>
                </header>

                <article>
                    @fragment('auth-card')
                        @include('flute::partials.social-login')

                        @if ($allowStandardAuth && !empty(social()->getAll()))
                            <div class="auth-divider">@t('def.or')</div>
                        @endif

                        @if ($allowStandardAuth)
                            @yoyo('login')
                        @endif
                    @endfragment
                </article>

                @if ($allowStandardAuth)
                    <footer class="auth-footer">
                        <p>@t('auth.no_account')
                            <x-link hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                                    href="{{ url('/register') }}">@t('auth.register')</x-link>
                        </p>
                    </footer>
                @endif
            </div>
        </section>
    </div>
@endpush
