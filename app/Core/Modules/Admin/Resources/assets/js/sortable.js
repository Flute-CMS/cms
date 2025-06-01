if (typeof Sortable === 'undefined') {
    throw new Error('Sortable.js is required but not loaded.');
}

(function () {
    function initializeSortables(container = document) {
        const sortableElements = container.querySelectorAll('[data-sortable]');

        sortableElements.forEach((el) => {
            if (el.dataset.sortableInitialized) return;

            Sortable.create(el, {
                group: el.dataset.sortableGroup || 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                handle: el.dataset.sortableHandle || '.reorder-icon',
                filter: '.non-draggable',
                ghostClass: 'sortable-ghost',
                onEnd: (e) => handleSortEnd(e, el),
                onMove: (event) => {
                    const level = $(event.to).parents('[data-sortable]').length;
                    const length = $(event.dragged).find(
                        '[data-sortable] > li',
                    ).length;

                    return !((length > 0 && level > 0) || level > 1);
                },
            });

            el.dataset.sortableInitialized = true;
        });
    }

    function serializeList(list) {
        const items = [];
        list.querySelectorAll(':scope > li').forEach((li) => {
            const item = {
                id: li.dataset.id,
                children: [],
            };

            const nestedList = li.querySelector(':scope > ol');
            if (nestedList) {
                item.children = serializeList(nestedList);
            }

            items.push(item);
        });
        return items;
    }

    function handleSortEnd(evt, sortableElement) {
        const rootList = sortableElement
            .closest('.sortable-container')
            .querySelector('ol');
        const sortedData = serializeList(rootList);

        htmx.trigger(
            sortableElement.closest('.sortable-container'),
            'sortEnd',
            {
                sortable: JSON.stringify(sortedData),
            },
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeSortables();

        document.body.addEventListener('htmx:afterSwap', function (event) {
            initializeSortables(event.target);
        });
    });

    window.initializeSortables = initializeSortables;
})();
