@if (!empty(footer()->socials()->all()))
    <div class="navbar__socials" itemscope itemtype="https://schema.org/Organization">
        @foreach (footer()->socials()->all() as $social)
            <a href="{{ $social->url }}" data-tooltip="@t($social->name)" aria-label="@t($social->name)" target="_blank"
                rel="noopener" itemprop="sameAs">
                <x-icon path="{!! $social->icon !!}" aria-hidden="true" />
            </a>
        @endforeach
    </div>
@endif
