@if (!empty(footer()->socials()->all()))
    <div class="footer__socials" itemscope itemtype="https://schema.org/Organization">
        <h6 class="footer__socials-title">@t('def.socials')</h6>

        <div class="footer__socials-container">
            @foreach (footer()->socials()->all() as $social)
                <a href="{{ $social->url }}" data-tooltip="@t($social->name)" aria-label="@t($social->name)" 
                   target="_blank" rel="noopener" itemprop="sameAs">
                    <x-icon path="{!! $social->icon !!}" aria-hidden="true" />
                </a>
            @endforeach
        </div>
    </div>
@endif
