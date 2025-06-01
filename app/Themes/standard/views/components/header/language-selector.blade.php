@if (count(config('lang.available')) > 1)
    <li hx-boost="false">
        <div class="navbar__lang" data-dropdown-open="langs" data-tooltip="{{ __('def.language') }}" aria-label="{{ __('def.language') }}" role="button" aria-haspopup="true" aria-expanded="false">
            <span>
                <img src="{{ asset('assets/img/langs/' . app()->getLang() . '.svg') }}" alt="{{ __('langs.' . app()->getLang()) }}"
                    loading="lazy" width="20" height="20">
            </span>
        </div>
        <div class="navbar__langs" data-dropdown="langs" aria-label="{{ __('def.language_select') }}" role="menu">
            @foreach (config('lang.available') as $lang)
                <a href="{{ url()->addParams(['lang' => $lang]) }}" @class(['active' => $lang === app()->getLang()]) 
                   hreflang="{{ $lang }}" lang="{{ $lang }}" role="menuitem" aria-current="{{ $lang === app()->getLang() ? 'page' : 'false' }}">
                    <span>
                        <img src="{{ asset('assets/img/langs/' . $lang . '.svg') }}" alt="{{ __('langs.' . $lang) }}"
                            loading="lazy" width="20" height="20">
                    </span>
                    <small>{{ __('langs.' . $lang) }}</small>
                </a>
            @endforeach
        </div>
    </li>
@endif
