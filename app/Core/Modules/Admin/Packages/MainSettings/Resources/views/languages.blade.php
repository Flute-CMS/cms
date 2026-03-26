<div class="languages-grid">
    @foreach ($languages as $lang)
        @php
            $isActive = in_array($lang, $available);
        @endphp
        <label class="language-card {{ $isActive ? 'language-card--active' : '' }}">
            <input type="checkbox" 
                   name="available[{{ $lang }}]" 
                   value="1"
                   {{ $isActive ? 'checked' : '' }}
                   class="language-card__input">
            <div class="language-card__content">
                <img src="{{ asset('assets/img/langs/' . $lang . '.svg') }}" 
                     alt="{{ $lang }}" 
                     class="language-card__flag"
                     onerror="this.style.display='none'">
                <span class="language-card__name">{{ __('langs.' . $lang) }}</span>
            </div>
            <div class="language-card__indicator"></div>
        </label>
    @endforeach
</div>
