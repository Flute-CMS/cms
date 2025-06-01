@props(['content'])

@php
    $id = 'popover-' . uniqid();
@endphp

<sup id="{{ $id }}" data-popover-trigger="true" data-popover-content="{{ $content }}" class="popover-trigger"
    aria-haspopup="true" aria-expanded="false" tabindex="0" role="button">
    <x-icon path="ph.regular.question" width="1em" height="1em" />
</sup>
