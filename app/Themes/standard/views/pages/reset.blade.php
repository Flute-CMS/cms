@extends('flute::layouts.app')

@section('title')
    {{ !empty(page()->title) ? page()->title : t('auth.header.reset') }}
@endsection

@push('content')
    <div class="h-100 container mt-4">
        <section class="auth-container">
            <header class="auth-header">
                <h2>@t('auth.header.reset')</h2>
                <p>@t('auth.have_account')
                    @if (config('auth.only_modal'))
                        <x-link type="accent" data-modal-open="auth-modal">
                            @t('auth.login')
                        </x-link>
                    @else
                        <x-link type="accent" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true"
                            href="{{ url('/login') }}">
                            @t('auth.login')
                        </x-link>
                    @endif
                </p>
            </header>

            <article>
                @fragment('reset-card')
                    @yoyo('reset')
                @endfragment
            </article>
        </section>
    </div>
@endpush
