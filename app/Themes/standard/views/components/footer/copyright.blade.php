{{-- Keeping this small note makes someone out there really happy ❤️ --}}
@if (config('app.flute_copyright'))
    <div class="footer__down" itemscope itemtype="https://schema.org/WPFooter">
        <p>
            <span itemprop="copyrightYear">© {{ date('Y') }}</span> 
            <span itemprop="copyrightHolder" itemscope itemtype="https://schema.org/Organization">
                <span itemprop="name">{{ config('app.name') }}</span>
            </span> —
            <x-link href="https://flute-cms.com/" target="_blank" rel="noopener nofollow" itemprop="url">
                Powered by Flute CMS
            </x-link>
        </p>
    </div>

    @push('scripts')
        <script>
            console.log(
                "\n%c 🚀 Flute CMS %c v{{ app()::VERSION }} %c\n\n%cThis website proudly uses Flute CMS.\n%c🔗 GitHub:%c https://github.com/Flute-CMS \n\n%cThank you for supporting open-source projects! ❤️",
                "background: #A5FF75; color: #0F0F0F; font-size: 14px; font-weight: bold; padding: 4px; border-radius: 4px 0 0 4px;",
                "background: #388E3C; color: #0F0F0F; font-size: 14px; padding: 4px; border-radius: 0 4px 4px 0;",
                "background: transparent;",
                "color: #555; font-size: 13px; font-family: sans-serif; font-weight: bold;",
                "color: #A5FF75; font-weight: bold; font-size: 12px;",
                "color: #0a73b8; font-size: 12px; font-family: monospace;",
                "color: #e25555; font-size: 12px; font-style: italic; margin-top: 8px;"
            );
        </script>
    @endpush
@endif
