@extends('admin::app')

@push('content')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Yoyo.url = '{{ url($slug) }}';
        });

        if (window.Yoyo) {
            Yoyo.url = '{{ url($slug) }}';
        }
    </script>

    <div hx-swap="morph:outerHTML" hx-encoding="multipart/form-data">
        @yoyo($screen, ['slug' => $slug])
    </div>
@endpush
