@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.header.login') }}
@endsection

@php
    $allowStandardAuth =
        !config('auth.only_social', false) || (config('auth.only_social', false) && sizeof(social()->getAll()) === 0);
@endphp

@push('content')
    <div class="h-100 container mt-4">
        <section class="auth-container">
            <header class="auth-header">
                <h2>@t('auth.header.login')</h2>

                @if ($allowStandardAuth)
                    <p>@t('auth.no_account') <x-link hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                            href="{{ url('/register') }}" type="accent">@t('auth.register')</x-link></p>
                @endif
            </header>

            <article>
                @fragment('auth-card')
                    @include('flute::partials.social-login')

                    @if ($allowStandardAuth)
                        @yoyo('login')
                    @endif
                @endfragment
            </article>
        </section>
    </div>
@endpush
