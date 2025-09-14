<div {{ $attributes->merge(['class' => 'modal dialog-container '.($size ? 'modal--'.$size : '')]) }} id="{{ $id }}"
    role="dialog" aria-hidden="true" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-content"
    data-a11y-dialog>

    <div class="modal__overlay dialog-overlay" tabindex="-1" @if($closeOnOverlay) data-a11y-dialog-hide @endif></div>

    <div class="modal__container dialog-content" role="document" tabindex="0">
        <header class="modal__header {{ empty($title) ? 'modal__header-withoutHeading' : '' }}">
            @if($title)
                <h4 class="modal__title" id="{{ $id }}-title">{{ $title }}</h4>
            @endif
            <button class="modal__close dialog-close" aria-label="Close modal" data-tooltip="{{ __('def.close') }}"
                data-a11y-dialog-hide="{{ $id }}"></button>
        </header>

        <div class="modal__content dialog-body" id="{{ $id }}-content">
            @if($loadUrl)
                <div hx-get="{{ $loadUrl }}" hx-target="{{ $loadTarget ?? '#'.$id.'-content' }}" hx-trigger="intersect"
                    hx-swap="innerHTML focus-scroll:false">

                    @if($skeleton)
                        {!! $skeleton !!}
                    @else
                        <div class="modal__content-loading">
                            <div class="skeleton modal__content-loading-box-large" aria-hidden="true"></div>
                            <div class="skeleton modal__content-loading-box" aria-hidden="true"></div>
                        </div>
                    @endif
                </div>
            @else
                {{ $slot }}
            @endif
        </div>

        @isset($footer)
            <footer class="modal__footer">{{ $footer }}</footer>
        @endisset
    </div>
</div>