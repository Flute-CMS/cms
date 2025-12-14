<fieldset class="row g-0 gx-1 gy-1 {{ $class }}" @if (!$morph) hx-swap="outerHTML" @endif>
    @if (!empty($title) || !empty($description))
        <div class="col">
            <legend class="mt-2">
                <h5>{{ __($title ?? '') }} @if ($popover)
                        <x-popover :content="$popover" />
                    @endif
                </h5>

                @if (!empty($description))
                    <small class="d-block text-muted text-balance mb-1">
                        {!! __($description ?? '') !!}
                    </small>
                @endif
            </legend>
        </div>
    @endif
    <div class="col-12 {{ !$vertical ? 'col-md-7' : '' }} h-100">
        <x-card>
            <div class="d-flex flex-column gap-3">
                @foreach ($manyForms as $key => $layouts)
                    @foreach ($layouts as $layout)
                        {!! $layout ?? '' !!}
                    @endforeach
                @endforeach
            </div>
        </x-card>

        @empty(!$commandBar)
            <div class="d-flex justify-content-end gap-2 px-4 py-3">
                @foreach ($commandBar as $command)
                    <div>
                        {!! $command !!}
                    </div>
                @endforeach
            </div>
        @endempty
    </div>
</fieldset>
