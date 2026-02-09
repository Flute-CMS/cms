@php
    $stackContent = trim($__env->yieldPushContent('content'));
@endphp

@if (!empty($stackContent))
    @stack('content')
@elseif(page()->hasAccessToEdit())
    <div class="pe-content-placeholder">
        <div class="pe-content-placeholder__icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-32-80a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,136Zm0,32a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,168Z"/></svg>
        </div>
        <div class="pe-content-placeholder__title">{{ __('widgets.content.name') }}</div>
        <div class="pe-content-placeholder__desc">{{ __('widgets.content.editor_hint') }}</div>
    </div>
@endif
