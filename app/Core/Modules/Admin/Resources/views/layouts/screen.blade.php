@extends('admin::app')

@push('content')
    <script>
        if (window.Yoyo) {
            Yoyo.url = '{{ url($slug) }}';
        }
    </script>

    <div hx-swap="morph:outerHTML" hx-encoding="multipart/form-data">
        @yoyo($screen, ['slug' => $slug])
    </div>
@endpush
