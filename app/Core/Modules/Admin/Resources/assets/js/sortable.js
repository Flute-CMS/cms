if (typeof Sortable === 'undefined') {
    throw new Error('Sortable.js is required but not loaded.');
}

(function () {
    // Track instances by element for proper lifecycle management
    const instances = new WeakMap();
    const debounceTimers = new WeakMap();

    function getContainerKey(container) {
        return container?.dataset?.containerKey ||
            (location.pathname + '#' + Array.from(document.querySelectorAll('.sortable-container')).indexOf(container));
    }

    /**
     * Returns the maximum depth of children within a dragged element.
     * 0 = leaf, 1 = one level of children, etc.
     */
    function getMaxChildDepth(el) {
        const li = (el.matches && el.matches('li.sortable-list-item'))
            ? el
            : el.closest('li.sortable-list-item');
        if (!li) return 0;
        const nested = li.querySelector(':scope > ol[data-sortable]');
        if (!nested) return 0;
        const children = nested.querySelectorAll(':scope > li');
        if (!children.length) return 0;
        let max = 0;
        children.forEach(child => {
            const d = 1 + getMaxChildDepth(child);
            if (d > max) max = d;
        });
        return max;
    }

    /**
     * Returns the depth (0-based) of a list element relative to its .sortable-container.
     * Root <ol> = 0, nested <ol> inside first <li> = 1, etc.
     */
    function getListDepth(listEl) {
        const container = listEl.closest('.sortable-container');
        if (!container) return 0;
        let depth = 0;
        let node = listEl.parentElement;
        while (node && node !== container) {
            if (node.matches && node.matches('li.sortable-list-item')) depth++;
            node = node.parentElement;
        }
        return depth;
    }

    /**
     * Called from group.put — runs BEFORE the dragged element enters the list,
     * so rejection never causes a visible ghost-snap jitter.
     */
    function canDropIntoList(toSortable, draggedEl) {
        const container = toSortable.el.closest('.sortable-container');
        if (!container) return true;
        const maxLevels = parseInt(container.dataset.maxLevels, 10) || 2;
        const listDepth  = getListDepth(toSortable.el);
        const dropDepth  = listDepth + 1;            // the item would sit here
        const childDepth = getMaxChildDepth(draggedEl); // it brings these extra levels
        return (dropDepth + childDepth) <= maxLevels;
    }

    function clearInsertionIndicators() {
        document.querySelectorAll('.insert-before, .insert-after').forEach(el => {
            el.classList.remove('insert-before', 'insert-after');
        });
    }

    function serializeList(list) {
        const items = [];
        list.querySelectorAll(':scope > li').forEach(li => {
            const item = { id: li.dataset.id, children: [] };
            const nested = li.querySelector(':scope > ol');
            if (nested) item.children = serializeList(nested);
            items.push(item);
        });
        return items;
    }

    function triggerSortEnd(container) {
        const rootList = container.querySelector(':scope > ol');
        if (!rootList) return;
        htmx.trigger(container, 'sortEnd', {
            sortable: JSON.stringify(serializeList(rootList)),
        });
    }

    function scheduleUpdate(li) {
        const container = li.closest('.sortable-container');
        if (!container) return;
        const existing = debounceTimers.get(container);
        if (existing) clearTimeout(existing);
        const key = getContainerKey(container);
        const t = setTimeout(() => {
            if (li.dataset.id) {
                sessionStorage.setItem('sortable:lastChanged:' + key, li.dataset.id);
            }
            triggerSortEnd(container);
            debounceTimers.delete(container);
        }, 400);
        debounceTimers.set(container, t);
    }

    function makeSortableOptions(el) {
        return {
            // Use group.put for depth validation — avoids ghost-snap jitter caused
            // by onMove returning false after the ghost already rendered.
            group: {
                name: el.dataset.sortableGroup || 'nested',
                put: (to, _from, draggedEl) => canDropIntoList(to, draggedEl),
            },
            animation: 120,
            // Lower threshold + invertSwap eliminates oscillation at item boundaries.
            swapThreshold: 0.5,
            invertSwap: true,
            invertedSwapThreshold: 0.5,
            // Allow dropping into empty nested lists with a small hit-area.
            emptyInsertThreshold: 6,
            direction: 'vertical',
            handle: el.dataset.sortableHandle || '.reorder-handle',
            filter: [
                'a', 'button', 'input', 'textarea', 'select',
                '[contenteditable]', '.non-draggable', '.toggle-children',
                '.js-collapse-all', '.js-expand-all', '.js-move-up', '.js-move-down',
            ].join(','),
            preventOnFilter: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            fallbackTolerance: 3,
            scroll: true,
            scrollSensitivity: 80,
            scrollSpeed: 10,
            onMove(evt) {
                // Only visual indicator — no depth blocking here (handled by group.put).
                clearInsertionIndicators();
                const related = evt.related?.closest('li.sortable-list-item');
                if (related) {
                    related.classList.add(evt.willInsertAfter ? 'insert-after' : 'insert-before');
                }
            },
            onEnd(evt) {
                clearInsertionIndicators();
                const container = el.closest('.sortable-container');
                if (!container) return;
                // Skip if nothing moved
                if (evt.oldIndex === evt.newIndex && evt.from === evt.to) return;
                const li = evt.item?.closest('li.sortable-list-item');
                const key = getContainerKey(container);
                if (li?.dataset.id) {
                    sessionStorage.setItem('sortable:lastChanged:' + key, li.dataset.id);
                }
                triggerSortEnd(container);
            },
        };
    }

    function initializeSortables(scope) {
        const root = (scope instanceof Element) ? scope : document;
        root.querySelectorAll('[data-sortable]').forEach(el => {
            if (instances.has(el) || !el.isConnected) return;
            instances.set(el, Sortable.create(el, makeSortableOptions(el)));
        });
        restoreCollapsedState(root);
    }

    function destroySortablesIn(scope) {
        if (!scope) return;
        const els = [];
        if (scope.matches?.('[data-sortable]')) els.push(scope);
        if (scope.querySelectorAll) {
            els.push(...scope.querySelectorAll('[data-sortable]'));
        }
        els.forEach(el => {
            const inst = instances.get(el);
            if (inst) {
                try { inst.destroy(); } catch (_) {}
                instances.delete(el);
            }
        });
    }

    function highlightLastChanged(target) {
        const container = target?.closest?.('.sortable-container')
            ?? target?.querySelector?.('.sortable-container');
        if (!container) return;
        const key = getContainerKey(container);
        const id  = sessionStorage.getItem('sortable:lastChanged:' + key);
        if (!id) return;
        sessionStorage.removeItem('sortable:lastChanged:' + key);
        const li = container.querySelector('li.sortable-list-item[data-id="' + id + '"]');
        if (!li) return;
        li.classList.add('just-changed');
        li.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        setTimeout(() => li.classList.remove('just-changed'), 1200);
    }

    // ── Collapsed state (persisted in localStorage) ──────────────────────────

    function getCollapsedSet(container) {
        const key = getContainerKey(container);
        try {
            const raw = localStorage.getItem('sortable:collapsed:' + key);
            return raw ? new Set(JSON.parse(raw)) : new Set();
        } catch (_) { return new Set(); }
    }

    function saveCollapsedSet(container, set) {
        const key = getContainerKey(container);
        try {
            localStorage.setItem('sortable:collapsed:' + key, JSON.stringify([...set]));
        } catch (_) {}
    }

    function updateCollapsedState(container, id, collapsed) {
        const set = getCollapsedSet(container);
        collapsed ? set.add(id) : set.delete(id);
        saveCollapsedSet(container, set);
    }

    function restoreCollapsedState(scope) {
        const containers = [];
        if (scope instanceof Element && scope.classList.contains('sortable-container')) {
            containers.push(scope);
        } else if (scope.querySelectorAll) {
            containers.push(...scope.querySelectorAll('.sortable-container'));
        }
        containers.forEach(container => {
            const set = getCollapsedSet(container);
            if (!set.size) return;
            container.querySelectorAll('li.sortable-list-item').forEach(li => {
                if (!set.has(li.dataset.id)) return;
                li.classList.add('collapsed');
                li.querySelector('.toggle-children')?.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => {
        initializeSortables();

        document.body.addEventListener('htmx:beforeSwap', evt => destroySortablesIn(evt.target));
        document.body.addEventListener('htmx:beforeCleanupElement', evt => destroySortablesIn(evt.target));
        document.body.addEventListener('htmx:afterSettle', evt => {
            initializeSortables(evt.detail.target);
            highlightLastChanged(evt.detail.target);
        });

        // ── Delegated click handlers ──────────────────────────────────────────

        document.body.addEventListener('click', e => {
            const toggleBtn = e.target.closest('.toggle-children');
            if (toggleBtn) {
                const li = toggleBtn.closest('li.sortable-list-item');
                if (!li) return;
                const collapsed = li.classList.toggle('collapsed');
                toggleBtn.setAttribute('aria-expanded', String(!collapsed));
                const container = li.closest('.sortable-container');
                if (container) updateCollapsedState(container, li.dataset.id, collapsed);
                e.preventDefault();
                return;
            }

            const collapseAll = e.target.closest('.js-collapse-all');
            if (collapseAll) {
                const container = collapseAll.closest('.sortable-container');
                if (!container) return;
                const ids = [];
                container.querySelectorAll('li.sortable-list-item').forEach(li => {
                    if (!li.querySelector(':scope > ol > li')) return;
                    li.classList.add('collapsed');
                    li.querySelector('.toggle-children')?.setAttribute('aria-expanded', 'false');
                    ids.push(li.dataset.id);
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
                    li.querySelector('.toggle-children')?.setAttribute('aria-expanded', 'true');
                });
                saveCollapsedSet(container, new Set());
                e.preventDefault();
                return;
            }

            const moveUp = e.target.closest('.js-move-up');
            if (moveUp) {
                const li = moveUp.closest('li.sortable-list-item');
                if (!li) return;
                const prev = li.previousElementSibling;
                if (prev) li.parentElement.insertBefore(li, prev);
                scheduleUpdate(li);
                e.preventDefault();
                return;
            }

            const moveDown = e.target.closest('.js-move-down');
            if (moveDown) {
                const li = moveDown.closest('li.sortable-list-item');
                if (!li) return;
                const next = li.nextElementSibling;
                if (next) li.parentElement.insertBefore(next, li);
                scheduleUpdate(li);
                e.preventDefault();
            }
        });
    });

    window.initializeSortables = initializeSortables;
    window.destroySortablesIn   = destroySortablesIn;
})();
