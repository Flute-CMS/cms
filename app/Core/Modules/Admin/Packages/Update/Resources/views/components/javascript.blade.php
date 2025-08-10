@push('head')
    @at('Core/Modules/Admin/Packages/Update/Resources/assets/js/update.js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.updates-panel .card').forEach((card, i) => {
                card.style.animationDelay = (i * 60) + 'ms';
            });
            document.querySelectorAll('.updates-panel details').forEach((d) => {
                d.addEventListener('toggle', () => {
                    if (d.open) {
                        const body = d.querySelector('.more-body, ul, .history-timeline');
                        if (body) body.style.animation = 'slideIn .2s ease-out';
                    }
                });
            });
        });
    </script>
@endpush