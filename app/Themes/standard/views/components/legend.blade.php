@props(['title', 'description' => null, 'icon' => null])

<div class="col p-0">
    <fieldset class="legend-block">
        <legend>
            <div class="legend-content">
                <div class="legend-text">
                    <h1>
                        @if($icon)
                            <div class="legend-icon">
                                <x-icon path="{{ $icon }}" />
                            </div>
                        @endif
                        {{ $title }}
                    </h1>

                    @if (! empty($description))
                        <p class="d-block text-balance mb-0">
                            {!! __($description) !!}
                        </p>
                    @endif
                </div>
            </div>
            @if (isset($slot) && ! $slot->isEmpty())
                <div class="legend-actions">
                    {{ $slot }}
                </div>
            @endif
        </legend>
    </fieldset>
</div>