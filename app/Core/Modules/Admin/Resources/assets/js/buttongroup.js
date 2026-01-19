function initButtonGroups(container = document) {
    container.querySelectorAll('[data-button-group]').forEach((group) => {
        if (group.dataset.buttonGroupInit) return;
        group.dataset.buttonGroupInit = '1';

        group.addEventListener('change', (e) => {
            if (e.target.classList.contains('button-group__input')) {
                group.querySelectorAll('.button-group__option').forEach((opt) => {
                    opt.classList.remove('button-group__option--active');
                });
                
                const label = e.target.closest('.button-group__option');
                if (label) {
                    label.classList.add('button-group__option--active');
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => initButtonGroups());
htmx.onLoad((el) => initButtonGroups(el));
