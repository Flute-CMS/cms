@props(['block', 'exception'])

<section class="invalid__widget" id="widget-{{ $block ? $block->getId() : '' }}">
    <div class="invalid__widget-icon">
        <x-icon path="ph.regular.smiley-melting" />
    </div>
    <h4 class="invalid__widget-title">
        {{ __('def.widget_error', [
            ':name' => mb_ucfirst($block ? $block->getWidget() : 'unknown'),
        ]) }}
    </h4>

    <small class="invalid__widget-small">
        {{ __('def.widget_error_desc') }}
    </small>

    <div class="invalid__widget-error">
        {{ $exception }}
    </div>

    @if ($block)
        <x-button type="error" size="tiny" hx-delete="{{ url('api/pages/delete-widget/' . $block->getId())->get() }}"
            hx-target="#widget-{{ $block->getId() }}" hx-swap="outerHTML">
            {{ __('def.delete_widget') }}
        </x-button>
    @endif
</section>
