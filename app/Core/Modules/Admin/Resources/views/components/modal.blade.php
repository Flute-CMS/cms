@props([
    'id' => 'default-modal',
    'title' => null,
    'loadUrl' => null,
    'loadTarget' => null,
    'footer' => null,
    'skeleton' => null,
    'closeOnOverlay' => true,
    'open' => false,
    'removeOnClose' => false,
    'type' => 'center',
    'size' => '',
    'containerClass' => '',
    'contentClass' => '',
    'withoutCloseButton' => false,
])

<div class="modal {{ $open ? 'is-open' : '' }} {{ $type == 'right' ? 'right_sidebar' : '' }} {{ $size ? 'modal--' . $size : '' }}"
    data-a11y-dialog="{{ $id }}" id="{{ $id }}"
    @if ($removeOnClose) data-remove-on-close @endif
    @if (!$open) aria-hidden="true" @endif {!! $attributes !!}>

    <div class="{{ $type == 'right' ? 'right_sidebar__overlay' : 'modal__overlay' }}"
        @if ($closeOnOverlay) data-a11y-dialog-hide @endif role="presentation">
    </div>

    <div class="{{ $type == 'right' ? 'right_sidebar__container' : 'modal__container' }} {{ $containerClass }}"
        role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        @if ($type != 'right')
            @if (!empty($title) || !$withoutCloseButton)
                <header @class([
                    'modal__header',
                    'modal__header-withoutHeading' => empty($title),
                ])>
                    @if ($title)
                        <h5 class="modal__title" id="{{ $id }}-title">
                            {{ $title }}
                        </h5>
                    @endif
                    @if (!$withoutCloseButton)
                        <button class="modal__close" aria-label="Close modal" data-tooltip="@t('def.close')"
                            data-a11y-dialog-hide="{{ $id }}"></button>
                    @endif
                </header>
            @endif
        @else
            <header class="right_sidebar__header">
                @if ($title)
                    <h5 class="right_sidebar__title" id="{{ $id }}-title">
                        {{ $title }}
                    </h5>
                @endif
                <button class="right_sidebar__close" aria-label="Close modal" data-tooltip="@t('def.close')"
                    data-a11y-dialog-hide="{{ $id }}"></button>
            </header>
        @endif
        <form class="{{ $type == 'right' ? 'right_sidebar__content' : 'modal__content' }} {{ $contentClass }}"
            id="{{ $id }}-content">
            @if ($loadUrl)
                <div hx-get="{{ $loadUrl }}" hx-target="{{ $loadTarget ?? '#' . $id . '-content' }}"
                    hx-trigger="intersect" hx-swap="innerHTML focus-scroll:false">
                    @if ($skeleton)
                        {!! $skeleton !!}
                    @else
                        <div class="modal__content-loading">
                            <div class="skeleton modal__content-loading-box-large"></div>
                            <div class="skeleton modal__content-loading-box"></div>
                        </div>
                    @endif
                </div>
            @else
                {{ $slot }}
            @endif
        </form>
        @if ($type != 'right' && $footer)
            <footer class="modal__footer">
                {{ $footer }}
            </footer>
        @endif
        @if ($type == 'right' && $footer)
            <div class="right_sidebar__footer">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
