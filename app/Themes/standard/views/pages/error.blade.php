@extends('flute::layouts.error')

@section('title')
    {{ $code.' - '.$message }}
@endsection

@push('content')
    <div class="err-page">
        {{-- Radial rings --}}
        <div class="err-page__rings" aria-hidden="true">
            <div class="err-page__ring err-page__ring--1"></div>
            <div class="err-page__ring err-page__ring--2"></div>
            <div class="err-page__ring err-page__ring--3"></div>
        </div>

        <div class="err-page__content">
            <h1 class="err-page__code">{{ $code }}</h1>
            <h2 class="err-page__title">{{ $message }}</h2>

            <p class="err-page__description">
                @if ($code == 404)
                    {{ __('error.404_description') }}
                @elseif ($code == 403)
                    {{ __('error.403_description') }}
                @elseif ($code == 500)
                    {{ __('error.500_description') }}
                @else
                    {{ __('error.default_description') }}
                @endif
            </p>

            <div class="err-page__actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                <x-button href="{{ url('/') }}" type="accent" size="medium">
                    <x-icon path="ph.regular.house" />
                    {{ __('def.back_home') }}
                </x-button>

                @if ($code == 404)
                    @php $prevUrl = (string)url()->previous(); @endphp
                    <x-button href="{{ $prevUrl }}" type="outline-accent" size="medium"
                        swap="{{ !str_contains($prevUrl, 'admin/') ? 'true' : 'false' }}">
                        <x-icon path="ph.regular.arrow-left" />
                        {{ __('error.go_back') }}
                    </x-button>
                @endif
            </div>
        </div>
    </div>
@endpush
