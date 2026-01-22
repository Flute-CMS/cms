/**
 * Filters component - reset functionality
 */
function initFilters(container = document) {
    container.querySelectorAll('[data-filters]').forEach((filtersEl) => {
        if (filtersEl.dataset.filtersInit) return;
        filtersEl.dataset.filtersInit = '1';

        const resetBtn = filtersEl.querySelector('[data-filters-reset]');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => resetFilters(filtersEl));
        }
    });
}

function resetFilters(filtersEl) {
    filtersEl.querySelectorAll('[data-button-group]').forEach((group) => {
        const defaultVal = group.dataset.default || '';
        const input = group.querySelector(`input[value="${defaultVal}"]`) ||
                      group.querySelector('input[value="all"]') ||
                      group.querySelector('input:first-of-type');
        if (input && !input.checked) {
            input.checked = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    filtersEl.querySelectorAll('[data-select]').forEach((select) => {
        const defaultVal = select.dataset.default || '';
        if (select.tomselect) {
            select.tomselect.setValue(defaultVal, true);
        } else {
            select.value = defaultVal;
        }
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });

    filtersEl.querySelectorAll('input[type="text"], input[type="date"], input[type="number"]').forEach((input) => {
        if (input.classList.contains('button-group__input')) return;
        const defaultVal = input.dataset.default || '';
        if (input.value !== defaultVal) {
            input.value = defaultVal;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    filtersEl.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        if (checkbox.classList.contains('button-group__input')) return;
        const defaultVal = checkbox.dataset.default === 'true' || checkbox.dataset.default === '1';
        if (checkbox.checked !== defaultVal) {
            checkbox.checked = defaultVal;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initFilters());
htmx.onLoad((el) => initFilters(el));
