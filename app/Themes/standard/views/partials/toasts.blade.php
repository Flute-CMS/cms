@php
    $toasts = app(\Flute\Core\Services\ToastService::class)->getToasts();
@endphp

@if (!empty($toasts))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toasts = @json($toasts);
            toasts.forEach(function(toast) {
                displayToast(toast);
            });
        });
    </script>
@endif
