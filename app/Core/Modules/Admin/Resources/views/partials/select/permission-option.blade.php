<div class="ts-opt--rich" style="max-width: calc(100% - 24px);">
    <div class="ts-opt__content max-w-full">
        <span class="ts-opt__label">{{ $text }}</span>
        @php
            $desc = __('permissions.' . $text);
            $hasDesc = $desc !== 'permissions.' . $text;
        @endphp
        @if($hasDesc)
            <span class="ts-opt__desc overflow-hidden text-ellipsis" data-tooltip="{{ $desc }}">{{ \Illuminate\Support\Str::limit($desc, 80) }}</span>
        @endif
    </div>
</div>
