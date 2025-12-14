<footer id="footer" itemscope itemtype="https://schema.org/WPFooter">
    <div class="container">
        <div class="footer">
            <div class="footer__content">
                <div class="footer__col">
                    <x-footer.logo />
                    <x-footer.socials />
                </div>
                <div class="footer__nav-cols">
                    <x-footer.links />
                </div>
            </div>
        </div>
        <div class="footer__bottom">
            <x-footer.copyright />

            @if(config('app.footer_additional'))
                <div class="footer__additional">
                    {!! markdown()->parse(config('app.footer_additional')) !!}
                </div>
            @endif
        </div>
    </div>
</footer>
