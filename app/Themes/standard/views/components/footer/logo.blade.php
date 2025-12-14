<a class="footer__logo footer__logo-dark" href="{{ url('/') }}" aria-label="{{ config('app.name') }}" itemprop="url">
    <img src="{{ asset(config('app.logo')) }}" loading="lazy" alt="{{ config('app.name') }}" width="150" height="40" itemprop="logo">
</a>

<a class="footer__logo footer__logo-light" href="{{ url('/') }}" aria-label="{{ config('app.name') }}" itemprop="url">
    <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" loading="lazy" alt="{{ config('app.name') }}" width="150" height="40" itemprop="logo">
</a>

@if (!empty(config('app.footer_description')))
    <p class="footer__description" itemprop="description">{!! __(config('app.footer_description', '')) !!}</p>
@endif
