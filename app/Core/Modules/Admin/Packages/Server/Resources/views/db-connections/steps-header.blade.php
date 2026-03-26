@props(['currentStep' => 1, 'description' => null])

@php
    $steps = [
        1 => __('admin-server.db_connection.steps.select_type'),
        2 => __('admin-server.db_connection.steps.select_db'),
        3 => __('admin-server.db_connection.steps.configure'),
    ];
@endphp

<div class="db-wizard">
    <div class="db-wizard__steps">
        @foreach ($steps as $num => $label)
            @php
                $state = $num < $currentStep ? 'done' : ($num === $currentStep ? 'active' : 'pending');
            @endphp
            <div class="db-wizard__step is-{{ $state }}">
                <span class="db-wizard__num">
                    @if ($state === 'done')
                        <x-icon path="ph.bold.check-bold" />
                    @else
                        {{ $num }}
                    @endif
                </span>
                <span class="db-wizard__label">{{ $label }}</span>
            </div>
        @endforeach
    </div>
    @if ($description)
        <p class="db-wizard__hint">{{ $description }}</p>
    @endif
</div>
