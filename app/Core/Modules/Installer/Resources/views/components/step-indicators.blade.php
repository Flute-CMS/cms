@props([
    'steps' => [],
    'currentStep' => 0
])

@if($currentStep > 0)
    <div class="step-indicators">
        @foreach ($steps as $index => $step)
        @php
            $itemClass = 'step-indicators__item';
            
            if ($index < $currentStep) {
                $itemClass .= ' step-indicators__item--completed';
            } elseif ($index === $currentStep) {
                $itemClass .= ' step-indicators__item--active';
            } else {
                $itemClass .= ' step-indicators__item--upcoming';
            }
        @endphp
        
        <div 
            class="{{ $itemClass }}" 
            data-step="{{ $step['id'] ?? $index }}" 

            @if($index < $step)
                hx-get="{{ route('installer.step', ['id' => $step['id'] ?? $index]) }}"
                hx-target="main"
                hx-push-url="true"
                hx-trigger="click"
            @endif

            title="{{ $step['title'] ?? 'Step ' . ($index + 1) }}"
            ></div>
        @endforeach
    </div>
@endif