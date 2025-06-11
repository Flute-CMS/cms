@php
    $stackContent = trim($__env->yieldPushContent('content'));
@endphp

@if (!empty($stackContent))
    @stack('content')
@endif
