@push('modals-container')
    <x-modal id="flute-admin-modal-{{ $key }}" :title="$title" :size="$size" :open="$open" hx-vals="{{ json_encode([
        'modalId' => $modalId,
        'modalParams' => $params ? encrypt()->encrypt($params) : null,
    ]) }}" :type="$type" removeOnClose="{{ $removeOnClose }}">
        @foreach ($manyForms as $formKey => $modal)
            @foreach ($modal as $item)
                {!! $item ?? '' !!}
            @endforeach
        @endforeach

        @if (empty($commandBar) && $withoutApplyButton && $withoutCloseButton)
        @else
            <x-slot:footer>
                <div class="modal-admin-footer d-flex justify-content-end align-items-center w-100 gap-3">
                    @if (! $withoutCloseButton && $type !== 'right')
                        <x-button type="outline-primary" data-a11y-dialog-hide="flute-admin-modal-{{ $key }}">
                            {{ $close }}
                        </x-button>
                    @endif

                    @empty($commandBar)
                        @if (! $withoutApplyButton)
                            <x-button id="submit-modal-{{ $key }}" class="{{ $type === 'right' ? 'w-100' : '' }}"
                                hx-include="#flute-admin-modal-{{ $key }}-content, #screen-container" yoyo:post="{{ $method }}">
                                {{ $apply }}
                            </x-button>
                        @endif
                    @else
                        {!! $commandBar !!}
                    @endempty
                </div>
            </x-slot:footer>
        @endif
    </x-modal>
@endpush