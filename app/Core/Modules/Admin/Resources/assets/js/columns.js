function initializeTable(wrapper) {
    const wrapperId = wrapper.getAttribute('yoyo:name');
    const columnsList = wrapper.querySelector('.table__columns');
    const tableId = columnsList
        ? columnsList.getAttribute('data-table-id') || wrapperId
        : wrapperId;

    const preferencesKey = `tableColumnPreferences-${tableId}`;
    const cookieKey = `columns_${tableId}`;

    let cookiePreferences = {};
    try {
        const cookieValue = getCookie(cookieKey);
        if (cookieValue) {
            cookiePreferences = JSON.parse(cookieValue);
        }
    } catch (e) {
        console.error('Error parsing cookie preferences:', e);
    }

    const localPreferences = JSON.parse(
        localStorage.getItem(preferencesKey) || '{}',
    );

    const preferences = { ...localPreferences, ...cookiePreferences };

    const checkboxes = wrapper.querySelectorAll(
        '.table__columns input[type="checkbox"]',
    );

    checkboxes.forEach((cb) => {
        const column = cb.value.replace(/_/g, '-');
        const show = preferences.hasOwnProperty(cb.value)
            ? preferences[cb.value]
            : cb.checked;
        cb.checked = show;
        toggleColumn(wrapper, column, show);
        cb.addEventListener('change', () => {
            const isChecked = cb.checked;
            preferences[cb.value] = isChecked;

            localStorage.setItem(preferencesKey, JSON.stringify(preferences));

            setCookie(cookieKey, JSON.stringify(preferences), 365);

            toggleColumn(wrapper, column, isChecked);
        });
    });

    function toggleColumn(wrapper, column, show) {
        const th = wrapper.querySelector(`th[data-column="${column}"]`);
        const tds = wrapper.querySelectorAll(`td[data-column="${column}"]`);

        if (!th && !tds.length) {
            return;
        }

        if (show) {
            if (th) {
                th.style.display = '';
                th.setAttribute('aria-hidden', 'false');
            }
            tds.forEach((td) => {
                td.style.display = '';
                td.setAttribute('aria-hidden', 'false');
            });
        } else {
            if (th) {
                th.style.display = 'none';
                th.setAttribute('aria-hidden', 'true');
            }
            tds.forEach((td) => {
                td.style.display = 'none';
                td.setAttribute('aria-hidden', 'true');
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.yoyo-wrapper').forEach(initializeTable);
});

document.addEventListener('htmx:afterSwap', (evt) => {
    evt.detail.elt.querySelectorAll('#screen-container').forEach(initializeTable);
});
