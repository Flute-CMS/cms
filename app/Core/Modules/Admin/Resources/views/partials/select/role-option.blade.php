<div class="ts-opt--rich">
    @if(!empty($item->color))
        <span class="ts-opt__color" style="background:{{ $item->color }}"></span>
    @endif
    <span>{{ $text }}</span>
</div>
