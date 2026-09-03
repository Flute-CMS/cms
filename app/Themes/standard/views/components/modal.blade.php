@props([
    'id' => 'default-modal',
    'title' => null,
    'loadUrl' => null,
    'loadTarget' => null,
    'footer' => null,
    'skeleton' => null,
    'closeOnOverlay' => true,
    'boosted' => false,
    'inline' => false,
    'size' => null,
])
@php
    $modalContent = view('flute::components._modal-body', [
        'attributes' => $attributes,
        'id' => $id,
        'title' => $title,
        'loadUrl' => $loadUrl,
        'loadTarget' => $loadTarget,
        'footer' => $footer,
        'skeleton' => $skeleton,
        'closeOnOverlay' => $closeOnOverlay,
        'size' => $size,
        'slot' => $slot,
    ])->render();
@endphp

 @if ($inline)
     @include('flute::components._modal-body', [
        'attributes' => $attributes,
        'id' => $id,
        'title' => $title,
        'loadUrl' => $loadUrl,
        'loadTarget' => $loadTarget,
        'footer' => $footer,
        'skeleton' => $skeleton,
        'closeOnOverlay' => $closeOnOverlay,
        'size' => $size,
        'slot' => $slot,
    ])
 @elseif(request()->htmx()->isBoosted() || $boosted)
     {{-- Через стек, иначе прямой вывод шаблона попадает в ответ ДО <!DOCTYPE> и htmx его не применяет --}}
     @push('modals-oob')
     <div hx-swap-oob="beforeend:#modals" hx-trigger="none">
         @include('flute::components._modal-body', [
            'attributes' => $attributes,
            'id' => $id,
            'title' => $title,
            'loadUrl' => $loadUrl,
            'loadTarget' => $loadTarget,
            'footer' => $footer,
            'skeleton' => $skeleton,
            'closeOnOverlay' => $closeOnOverlay,
            'size' => $size,
            'slot' => $slot,
        ])
     </div>
     @endpush
 @else
     @push('modals')
         @include('flute::components._modal-body', [
            'attributes' => $attributes,
            'id' => $id,
            'title' => $title,
            'loadUrl' => $loadUrl,
            'loadTarget' => $loadTarget,
            'footer' => $footer,
            'skeleton' => $skeleton,
            'closeOnOverlay' => $closeOnOverlay,
            'size' => $size,
            'slot' => $slot,
        ])
     @endpush
 @endif