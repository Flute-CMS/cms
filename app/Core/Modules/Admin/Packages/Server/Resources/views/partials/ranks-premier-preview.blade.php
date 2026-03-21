<div class="ranks-card__preview-inline">
    <div class="ranks-card__preview-label">{{ __('admin-server.ranks_section.preview') }}</div>
    <div class="ranks-card__preview-badges">
        @php
            $premierRanks = [
                ['class' => 'gray-rank', 'points' => '0–4 999'],
                ['class' => 'wblue-rank', 'points' => '5 000–9 999'],
                ['class' => 'blue-rank', 'points' => '10 000–14 999'],
                ['class' => 'purple-rank', 'points' => '15 000–19 999'],
                ['class' => 'pink-rank', 'points' => '20 000–24 999'],
                ['class' => 'red-rank', 'points' => '25 000–29 999'],
                ['class' => 'gold-rank', 'points' => '30 000+'],
            ];
        @endphp
        @foreach ($premierRanks as $r)
            <div class="ranks-card__premier-row">
                <div class="premier-rank {{ $r['class'] }}">{{ explode('–', str_replace(' ', '', $r['points']))[0] }}</div>
                <span class="ranks-card__premier-range">{{ $r['points'] }}</span>
            </div>
        @endforeach
    </div>
</div>
