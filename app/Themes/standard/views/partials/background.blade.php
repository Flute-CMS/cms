@if (config('app.bg_image'))
    @push('styles')
        <style>
            body {
                background-image: url(@asset(config('app.bg_image')));
                background-repeat: no-repeat;
                background-attachment: fixed;
                background-position: center center;
                background-size: cover;
            }
        </style>
    @endpush
@endif
