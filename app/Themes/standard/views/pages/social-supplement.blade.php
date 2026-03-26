@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.supplement.header') }}
@endsection

@push('content')
    <div class="container">
        <section class="auth-container">
            <div class="auth-card">
                <header class="auth-header">
                    <h2>@t('auth.supplement.header')</h2>
                    <p class="text-center text-muted">@t('auth.supplement.description')</p>
                </header>

                <article>
                    @yoyo('social-supplement')
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
