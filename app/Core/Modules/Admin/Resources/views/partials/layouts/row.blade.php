<fieldset class="row g-0 gx-1 gy-1 {{ $class }}">
    @if (!empty($title) || !empty($description))
        <div class="col">
            <legend class="mt-2">
                <h5>{{ __($title ?? '') }} @if ($popover)
                        <x-popover :content="$popover" />
                    @endif
                </h5>

                @if (!empty($description))
                    <small class="d-block text-muted mb-1 text-balance">
                        {!! __($description ?? '') !!}
                    </small>
                @endif
            </legend>
        </div>
    @endif

    <div class="d-flex flex-column gap-3">
        {!! $form ?? '' !!}
    </div>
</fieldset>
