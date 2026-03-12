@props([
    'steps' => [],
    'currentStep' => 0,
])

@php
    $stepLabels = [
        1 => __('install.step_labels.check'),
        2 => __('install.step_labels.database'),
        3 => __('install.step_labels.account'),
        4 => __('install.step_labels.languages'),
        5 => __('install.step_labels.modules'),
        6 => __('install.step_labels.launch'),
    ];
@endphp

@if ($currentStep > 0)
    <div class="step-nav">
        @foreach ($steps as $index => $stepName)
            @php
                if ($index < $currentStep) {
                    $state = 'is-done';
                } elseif ($index === $currentStep) {
                    $state = 'is-active';
                } else {
                    $state = '';
                }
            @endphp

            <div class="step-nav__item {{ $state }}">
                <span class="step-nav__dot"></span>
                <span class="step-nav__label">{{ $stepLabels[$index] ?? '' }}</span>
            </div>

            @if (!$loop->last)
                <span class="step-nav__line"></span>
            @endif
        @endforeach
    </div>
@endif
