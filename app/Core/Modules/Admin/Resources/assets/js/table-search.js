(() => {
    const updateUrl = (value) => {
        try {
            const url = new URL(window.location.href);
            const trimmed = (value || '').trim();

            if (trimmed) {
                url.searchParams.set('table-search', trimmed);
                url.searchParams.delete('page');
            } else {
                url.searchParams.delete('table-search');
                url.searchParams.delete('page');
            }

            url.searchParams.delete('yoyo-id');
            url.searchParams.delete('component');

            window.history.replaceState(window.history.state, '', url.toString());
        } catch (_) {
            // ignore
        }
    };

    const bind = (root) => {
        const scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('input[name="table-search"]').forEach((input) => {
            if (input.dataset.tableSearchBound === '1') return;
            input.dataset.tableSearchBound = '1';

            let t = 0;
            const onInput = () => {
                window.clearTimeout(t);
                t = window.setTimeout(() => updateUrl(input.value), 250);
            };

            input.addEventListener('input', onInput);
            input.addEventListener('change', onInput);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => bind(document));
    } else {
        bind(document);
    }

    if (typeof window.htmx !== 'undefined') {
        window.htmx.on('htmx:afterSwap', (evt) => {
            try {
                bind(evt.detail && evt.detail.target ? evt.detail.target : evt.target);
            } catch (_) {
                // ignore
            }
        });
    }
})();

