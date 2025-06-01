@extends('flute::layouts.error')

@section('title')
    {{ $code.' - '.$message }}
@endsection

@push('content')
    <div class="status-page error-page">
        <div class="error-container">
            <div class="error-code-container">
                <h1 class="error-code">{{ $code }}</h1>
                <div class="error-code-shadow"></div>
            </div>

            <div class="error-content">
                <h2 class="error-message">{{ $message }}</h2>
                <p class="error-description">
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

                <div class="error-actions" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                    <x-button href="{{ url('/') }}" type="accent" class="error-button">
                        <x-icon path="ph.regular.house" />
                        {{ __('def.back_home') }}
                    </x-button>

                    @if ($code == 404)
                        <x-button href="{{ url()->previous() }}" swap="{{ !str_contains(url()->previous(), 'admin/') ? 'true' : 'false' }}" type="outline-accent" class="error-button">
                            <x-icon path="ph.regular.arrow-left" />
                            {{ __('error.go_back') }}
                        </x-button>
                    @endif
                </div>
            </div>

            <div class="error-decoration">
                @if ($code == 404)
                    <div class="error-illustration">
                        <div class="search-animation">
                            <div class="magnifier">
                                <div class="magnifier-handle"></div>
                                <div class="magnifier-glass"></div>
                                <div class="magnifier-reflection"></div>
                            </div>
                            <div class="question-marks">
                                <div class="question-mark q1">?</div>
                                <div class="question-mark q2">?</div>
                                <div class="question-mark q3">?</div>
                            </div>
                        </div>
                    </div>
                    {{-- @elseif ($code == 403)
                                            <div class="error-illustration">
                                                <div class="lock-animation">
                                                    <div class="lock-body"></div>
                                                    <div class="lock-hook"></div>
                                                    <div class="lock-shackle"></div>
                                                </div>
                                            </div>
                                        @elseif ($code == 500)
                                            <div class="error-illustration">
                                                <div class="server-animation">
                                                    <div class="server"></div>
                                                    <div class="server-lights">
                                                        <div class="light light-1"></div>
                                                        <div class="light light-2"></div>
                                                        <div class="light light-3"></div>
                                                    </div>
                                                    <div class="server-smoke"></div>
                                                </div>
                                            </div> --}}
                @else
                    <div class="error-illustration">
                        <div class="generic-error-animation">
                            <div class="error-icon"></div>
                            <div class="error-pulse"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="error-particles">
            @for ($i = 1; $i <= 20; $i++)
                <div class="particle particle-{{ $i }}"></div>
            @endfor
        </div>
    </div>

    <script>
        document.addEventListener('htmx:load', function () {
            const particles = document.querySelectorAll('.particle');
            particles.forEach(particle => {
                const randomX = Math.random() * 100 - 50;
                const randomY = Math.random() * 100 - 50;
                const randomDelay = Math.random() * 5;
                const randomDuration = 3 + Math.random() * 7;

                particle.classList.add('particle-animation');
                particle.style.setProperty('--x', `${randomX}px`);
                particle.style.setProperty('--y', `${randomY}px`);
                particle.style.setProperty('--delay', `${randomDelay}s`);
                particle.style.setProperty('--duration', `${randomDuration}s`);
            });
        });
    </script>
@endpush