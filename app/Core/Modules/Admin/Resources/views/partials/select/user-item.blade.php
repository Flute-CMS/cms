<div class="ts-opt--rich">
    @if(!empty($item->avatar))
        <img class="ts-opt__avatar ts-opt__avatar--sm" src="{{ url($item->avatar) }}" alt="" loading="lazy" />
    @else
        <div class="ts-opt__avatar ts-opt__avatar--sm ts-opt__avatar--placeholder">
            {{ mb_substr($text, 0, 1) }}
        </div>
    @endif
    <span>{{ $text }}</span>
</div>
