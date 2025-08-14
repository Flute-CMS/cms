if (typeof Sortable === 'undefined') {
    throw new Error('Sortable.js is required but not loaded.');
}

(function () {
    const debounceTimers = new WeakMap();

    function getContainerKey(container) {
        return container?.dataset?.containerKey || (location.pathname + '#' + Array.from(document.querySelectorAll('.sortable-container')).indexOf(container));
    }

    function initializeSortables(container = document) {
        const sortableElements = container.querySelectorAll('[data-sortable]');

        sortableElements.forEach((el) => {
            if (el.dataset.sortableInitialized) return;

            Sortable.create(el, {
                group: el.dataset.sortableGroup || 'nested',
                animation: 180,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                direction: 'vertical',
                handle: el.dataset.sortableHandle || '.reorder-handle',
                // Do not start drag when interacting with controls
                filter: 'a,button,input,textarea,select,[contenteditable],.non-draggable,.toggle-children,.js-collapse-all,.js-expand-all,.js-move-up,.js-move-down',
                preventOnFilter: true,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                // Better mobile handling + autoscroll during drag
                fallbackTolerance: 8,
                scroll: true,
                scrollSensitivity: 60,
                scrollSpeed: 12,
                onEnd: (e) => {
                    clearInsertionIndicators();
                    // remember last changed id for highlight after swap
                    const li = e.item?.closest('li.sortable-list-item');
                    const containerEl = el.closest('.sortable-container');
                    if (li && containerEl) {
                        const key = getContainerKey(containerEl);
                        sessionStorage.setItem('sortable:lastChanged:' + key, li.dataset.id || '');
                    }
                    handleSortEnd(e, el);
                },
                onMove: (event) => {
                    // Show insertion indicator line
                    clearInsertionIndicators(document);
                    const related = event.related && event.related.closest('li.sortable-list-item');
                    if (related) {
                        related.classList.add(event.willInsertAfter ? 'insert-after' : 'insert-before');
                    }

                    const level = $(event.to).parents('[data-sortable]').length;
                    const length = $(event.dragged).find(
                        '[data-sortable] > li',
                    ).length;

                    return !((length > 0 && level > 0) || level > 1);
                },
            });

            el.dataset.sortableInitialized = true;
        });

        // restore collapsed state
        restoreCollapsedState(container);
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

    function clearInsertionIndicators(container = document) {
        container.querySelectorAll('.insert-before, .insert-after').forEach((el) => {
            el.classList.remove('insert-before', 'insert-after');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeSortables();

        document.body.addEventListener('htmx:afterSwap', function (event) {
            initializeSortables(event.target);
            // highlight last changed item if present
            const container = event.target.closest?.('.sortable-container') || event.target.querySelector?.('.sortable-container');
            if (container) {
                const key = getContainerKey(container);
                const id = sessionStorage.getItem('sortable:lastChanged:' + key);
                if (id) {
                    const li = container.querySelector('li.sortable-list-item[data-id="' + id + '"]');
                    if (li) {
                        li.classList.add('just-changed');
                        li.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => li.classList.remove('just-changed'), 1200);
                    }
                    sessionStorage.removeItem('sortable:lastChanged:' + key);
                }
            }
        });

        // Delegated handlers for collapsing/expanding
        document.body.addEventListener('click', function (e) {
            const toggleBtn = e.target.closest('.toggle-children');
            if (toggleBtn) {
                const li = toggleBtn.closest('li.sortable-list-item');
                if (!li) return;
                const collapsed = li.classList.toggle('collapsed');
                toggleBtn.setAttribute('aria-expanded', (!collapsed).toString());
                const container = li.closest('.sortable-container');
                if (container) {
                    updateCollapsedState(container, li.dataset.id, collapsed);
                }
                e.preventDefault();
                return;
            }

            const collapseAll = e.target.closest('.js-collapse-all');
            if (collapseAll) {
                const container = collapseAll.closest('.sortable-container');
                if (!container) return;
                const ids = [];
                container.querySelectorAll('li.sortable-list-item').forEach(li => {
                    if (li.querySelector(':scope > ol > li')) {
                        li.classList.add('collapsed');
                        const btn = li.querySelector('.toggle-children');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                        ids.push(li.dataset.id);
                    }
                });
                saveCollapsedSet(container, new Set(ids));
                e.preventDefault();
                return;
            }

            const expandAll = e.target.closest('.js-expand-all');
            if (expandAll) {
                const container = expandAll.closest('.sortable-container');
                if (!container) return;
                container.querySelectorAll('li.sortable-list-item.collapsed').forEach(li => {
                    li.classList.remove('collapsed');
                    const btn = li.querySelector('.toggle-children');
                    if (btn) btn.setAttribute('aria-expanded', 'true');
                });
                saveCollapsedSet(container, new Set());
                e.preventDefault();
                return;
            }

            // Move up/down buttons
            const moveUp = e.target.closest('.js-move-up');
            if (moveUp) {
                const li = moveUp.closest('li.sortable-list-item');
                if (!li) return;
                const parent = li.parentElement;
                const prev = li.previousElementSibling;
                if (prev) parent.insertBefore(li, prev);
                scheduleUpdate(li);
                e.preventDefault();
                return;
            }

            const moveDown = e.target.closest('.js-move-down');
            if (moveDown) {
                const li = moveDown.closest('li.sortable-list-item');
                if (!li) return;
                const parent = li.parentElement;
                const next = li.nextElementSibling;
                if (next) parent.insertBefore(next, li);
                scheduleUpdate(li);
                e.preventDefault();
            }
        });
    });

    function triggerSortUpdateFrom(el) {
        const container = el.closest('.sortable-container');
        if (!container) return;
        // remember last changed id for highlight after swap
        const key = getContainerKey(container);
        const li = el.closest('li.sortable-list-item');
        if (li) sessionStorage.setItem('sortable:lastChanged:' + key, li.dataset.id || '');
        const rootList = container.querySelector('ol');
        const sortedData = serializeList(rootList);
        htmx.trigger(
            container,
            'sortEnd',
            {
                sortable: JSON.stringify(sortedData),
            },
        );
    }

    function scheduleUpdate(el) {
        const container = el.closest('.sortable-container');
        if (!container) return;
        const existing = debounceTimers.get(container);
        if (existing) clearTimeout(existing);
        const t = setTimeout(() => {
            triggerSortUpdateFrom(el);
            debounceTimers.delete(container);
        }, 600);
        debounceTimers.set(container, t);
    }

    function getCollapsedSet(container) {
        const key = getContainerKey(container);
        try {
            const raw = localStorage.getItem('sortable:collapsed:' + key);
            if (!raw) return new Set();
            return new Set(JSON.parse(raw));
        } catch (_) {
            return new Set();
        }
    }

    function saveCollapsedSet(container, set) {
        const key = getContainerKey(container);
        try {
            localStorage.setItem('sortable:collapsed:' + key, JSON.stringify(Array.from(set)));
        } catch (_) {}
    }

    function updateCollapsedState(container, id, collapsed) {
        const set = getCollapsedSet(container);
        if (collapsed) set.add(id); else set.delete(id);
        saveCollapsedSet(container, set);
    }

    function restoreCollapsedState(scope) {
        const container = scope.closest ? (scope.closest('.sortable-container') || scope) : scope;
        if (!container || !container.classList || !container.classList.contains('sortable-container')) return;
        const set = getCollapsedSet(container);
        if (set.size === 0) return;
        container.querySelectorAll('li.sortable-list-item').forEach(li => {
            if (set.has(li.dataset.id)) {
                li.classList.add('collapsed');
                const btn = li.querySelector('.toggle-children');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    window.initializeSortables = initializeSortables;
})();
