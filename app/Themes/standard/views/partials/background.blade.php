@if (config('app.bg_image') || config('app.bg_image_light'))
    @push('styles')
        <style>
            @if (config('app.bg_image'))
                html[data-theme="dark"] body {
                    background-image: url(@asset(config('app.bg_image')));
                    background-repeat: no-repeat;
                    background-attachment: fixed;
                    background-position: center center;
                    background-size: cover;
                }
            @endif

            @if (config('app.bg_image_light'))
                html[data-theme="light"] body {
                    background-image: url(@asset(config('app.bg_image_light')));
                    background-repeat: no-repeat;
                    background-attachment: fixed;
                    background-position: center center;
                    background-size: cover;
                }
            @endif
        </style>
    @endpush
@endif
