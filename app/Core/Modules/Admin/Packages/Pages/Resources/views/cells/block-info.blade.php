<div class="page-block">
    <div class="page-block-header">
        <div class="page-block-widget">
            <div class="page-block-widget-icon">
                <x-icon path="ph.regular.squares-four" />
            </div>
            {{ $block->widget }}
        </div>
    </div>
    
    @if($block->settings && $block->settings !== '{}')
        <div class="page-block-settings">{{ $block->settings }}</div>
    @endif
</div> 