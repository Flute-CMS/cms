@push('head')
    @at('Core/Modules/Admin/Packages/Update/Resources/assets/js/update.js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('.su');
            if (!root) return;

            root.querySelectorAll('details').forEach((d) => {
                d.addEventListener('toggle', () => {
                    if (d.open) {
                        const body = d.querySelector('.su-disc-body, .su-hist');
                        if (body) body.style.animation = 'suFadeUp .2s ease-out';
                    }
                });
            });
        });
    </script>
@endpush
