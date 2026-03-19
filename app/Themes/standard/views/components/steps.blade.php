@props([
    'name' => '',
    'linear' => true,
    'orientation' => 'horizontal', // 'horizontal' | 'vertical'
    'variant' => null, // null (default) | 'progress' | 'minimal'
])

@php
    $name = preg_replace('/[^\w-]/', '_', $name);
    $linear = filter_var($linear, FILTER_VALIDATE_BOOLEAN);

    $classes = 'steps-container';
    if ($orientation === 'vertical') $classes .= ' steps--vertical';
    if ($variant === 'progress') $classes .= ' steps--progress';
    if ($variant === 'minimal') $classes .= ' steps--minimal';
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}
    data-steps-id="steps__{{ $name }}"
    data-steps-linear="{{ $linear ? 'true' : 'false' }}">
    <nav class="steps-nav" aria-label="{{ __('def.steps') }}">
        <ol class="steps-list" role="list">
            {{ $headings }}
        </ol>
    </nav>
    {{ $slot }}
</div>
