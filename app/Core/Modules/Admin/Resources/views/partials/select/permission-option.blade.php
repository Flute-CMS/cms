<div class="ts-opt--rich">
    <x-icon path="ph.regular.shield-check" style="opacity:.5" />
    <div class="ts-opt__content">
        <span class="ts-opt__label">{{ $text }}</span>
        @php
            $parts = explode('.', $text);
            $scope = count($parts) > 1 ? $parts[0] : null;
        @endphp
        @if($scope)
            <span class="ts-opt__desc">{{ $scope }}</span>
        @endif
    </div>
</div>
