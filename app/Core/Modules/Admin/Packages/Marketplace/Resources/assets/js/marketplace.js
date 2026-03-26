/* Marketplace JavaScript */
(function () {
    var doc = document;
    var bound = false;

    function init() {
        if (bound) return;
        bound = true;

        doc.addEventListener('click', function (e) {
            // Skip buttons (install/update handled by Yoyo)
            if (e.target.closest('button, select, input')) return;

            var card = e.target.closest('.mp-card');
            if (!card) return;

            var link = card.querySelector('a.cover') || card.querySelector('a.title');
            var href = link ? link.getAttribute('href') : null;
            if (!href) return;

            e.preventDefault();

            if (e.ctrlKey || e.metaKey) {
                window.open(href, '_blank');
            } else if (window.htmx) {
                htmx.ajax('GET', href, { target: '#main', swap: 'innerHTML' });
            } else {
                window.location.href = href;
            }
        });
    }

    if (doc.readyState !== 'loading') {
        init();
    } else {
        doc.addEventListener('DOMContentLoaded', init);
    }
})();
