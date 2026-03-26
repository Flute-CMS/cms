@if (config('app.bg_image') || config('app.bg_image_light'))
    @php
        $_currentTheme = config('app.change_theme', true)
            ? cookie()->get('theme', config('app.default_theme', 'dark'))
            : config('app.default_theme', 'dark');
        $_bgToPreload = $_currentTheme === 'light'
            ? config('app.bg_image_light')
            : config('app.bg_image');
    @endphp
    @if ($_bgToPreload)
        @push('head')
            <link rel="preload" href="@asset($_bgToPreload)" as="image" fetchpriority="low">
        @endpush
    @endif
    @push('styles')
        <style>
            @if (config('app.bg_image'))
                html[data-theme="dark"] body,
                html[data-theme="dark"] .content-frame {
                    background-image: url(@asset(config('app.bg_image')));
                    background-repeat: no-repeat;
                    background-position: center center;
                    background-size: cover;
                    background-attachment: scroll;
                }
            @endif

            @if (config('app.bg_image_light'))
                html[data-theme="light"] body,
                html[data-theme="light"] .content-frame {
                    background-image: url(@asset(config('app.bg_image_light')));
                    background-repeat: no-repeat;
                    background-position: center center;
                    background-size: cover;
                    background-attachment: scroll;
                }
            @endif
        </style>
    @endpush
@endif
