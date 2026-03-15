<div class="ts-opt--rich">
    @if(!empty($item->avatar))
        <img class="ts-opt__avatar" src="{{ url($item->avatar) }}" alt="" loading="lazy" />
    @else
        <div class="ts-opt__avatar ts-opt__avatar--placeholder">
            {{ mb_substr($text, 0, 1) }}
        </div>
    @endif
    <div class="ts-opt__content">
        <span class="ts-opt__label">{{ $text }}</span>
        @if(!empty($item->email))
            <span class="ts-opt__desc">{{ $item->email }}</span>
        @elseif(!empty($item->login))
            <span class="ts-opt__desc">{{ $item->login }}</span>
        @endif
    </div>
</div>
