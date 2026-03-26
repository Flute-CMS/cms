document.addEventListener('DOMContentLoaded', () => {
    // Section collapse (See N more)
    document.querySelectorAll('.profile__section-more').forEach(btn => {
        btn.addEventListener('click', () => {
            const section = btn.closest('.profile__section');
            if (!section) return;

            section.querySelectorAll('[data-collapsed]').forEach(el => {
                el.removeAttribute('data-collapsed');
                el.style.display = '';
            });
            btn.remove();
        });
    });
});
