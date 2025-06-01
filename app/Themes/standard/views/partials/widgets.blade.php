<section class="container">
    <div class="row">
        <div class="col-md-12">
            @if (!empty(page()->getBlocks()))
                <div class="page-widgets" id="page-widgets">
                    @foreach (page()->getBlocks() as $block)
                        @php
                            $gridstack = @json_decode($block->gridstack, true) ?? [];

                            $style = sprintf(
                                'grid-column: %d / span %d; grid-row: %d / span %d;',
                                ($gridstack['x'] ?? 0) + 1,
                                $gridstack['w'] ?? 1,
                                ($gridstack['y'] ?? 0) + 1,
                                $gridstack['h'] ?? 1,
                            );
                            
                            $widgetContent = page()->renderWidget((int) $block->getId());
                        @endphp

                        @if ($widgetContent !== null)
                            <section data-widget-id="{{ $block->getId() }}" data-widget-name="{{ $block->getWidget() }}"
                                style="{{ $style }}">
                                {!! $widgetContent !!}
                            </section>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
