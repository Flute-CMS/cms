function initSelectionForTable(table) {
    if (table.dataset.selectionInitialized) {
        return;
    }
    table.dataset.selectionInitialized = "true";

    const tableId = table.id;
    const selectAll = document.querySelector(`#select-all-${tableId}`);
    if (!selectAll) return;

    const bulkBar = document.querySelector(`#bulk-actions-${tableId}`);
    const storageKey = `tableSelection-${tableId}`;

    const getSelection = () => JSON.parse(localStorage.getItem(storageKey) || "[]");
    const saveSelection = (ids) => localStorage.setItem(storageKey, JSON.stringify(Array.from(new Set(ids))));

    const updateBulkUI = () => {
        if (!bulkBar) return;

        const selectedIds = getSelection();
        const bulkCount = bulkBar.querySelector(".bulk-selected-count");
        const wantShow = selectedIds.length > 0;
        const isVisible = bulkBar.classList.contains('is-visible');
        const ANIM_MS = 200;

        if (wantShow && !isVisible) {
            bulkBar.style.display = 'block';
            requestAnimationFrame(() => {
                bulkBar.classList.remove('is-hiding');
                bulkBar.classList.add('is-visible');
            });
        } else if (!wantShow && isVisible) {
            bulkBar.classList.remove('is-visible');
            bulkBar.classList.add('is-hiding');
            setTimeout(() => {
                bulkBar.style.display = 'none';
                bulkBar.classList.remove('is-hiding');
            }, ANIM_MS);
        } else if (!wantShow) {
            bulkBar.style.display = 'none';
            bulkBar.classList.remove('is-visible', 'is-hiding');
        }

        if (bulkCount) {
            bulkCount.textContent = selectedIds.length;
        }

        bulkBar.querySelectorAll('input[type="hidden"][name="selected[]"]').forEach(n => n.remove());
        selectedIds.forEach(id => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "selected[]";
            input.value = String(id);
            bulkBar.appendChild(input);
        });

        bulkBar.querySelectorAll('.btn').forEach(btn => {
            const label = (btn.getAttribute('aria-label') || btn.textContent || '').trim();
            if (label && !btn.getAttribute('aria-label')) {
                btn.setAttribute('aria-label', label);
            }
            if (label && !btn.getAttribute('data-tooltip')) {
                btn.setAttribute('data-tooltip', label);
            }
        });

        const checkboxes = table.querySelectorAll('tbody input[name="selected[]"]');
        const allOnPageChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
        selectAll.checked = allOnPageChecked;
    };

    const setRowHighlight = (cb) => {
        const tr = cb.closest("tr");
        if (tr) {
            tr.classList.toggle("row-selected", cb.checked);
        }
    };

    selectAll.addEventListener("change", () => {
        const selectedIds = new Set(getSelection());
        const checkboxes = table.querySelectorAll('tbody input[name="selected[]"]');

        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
            const id = String(cb.value);
            if (selectAll.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            setRowHighlight(cb);
        });

        saveSelection(Array.from(selectedIds));
        updateBulkUI();
    });

    const checkboxes = table.querySelectorAll('tbody input[name="selected[]"]');
    checkboxes.forEach(cb => {
        const id = String(cb.value);
        cb.checked = new Set(getSelection()).has(id);
        setRowHighlight(cb);

        cb.addEventListener("change", () => {
            const selectedIds = new Set(getSelection());
            if (cb.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            saveSelection(Array.from(selectedIds));
            setRowHighlight(cb);
            updateBulkUI();
        });
    });

    const clearBtn = bulkBar?.querySelector('.bulk-clear-btn');
    if (clearBtn) {
        clearBtn.onclick = () => {
            saveSelection([]);
            table.querySelectorAll('tbody input[name="selected[]"]').forEach(cb => {
                cb.checked = false;
                setRowHighlight(cb);
            });
            updateBulkUI();
        };
    }

    updateBulkUI();
}

function updateAllBulkBars() {
    const ANIM_MS = 200;
    document.querySelectorAll('div[data-bulk-table]').forEach(bar => {
        const tableId = bar.dataset.bulkTable;
        if (!tableId) return;

        const storageKey = `tableSelection-${tableId}`;
        const selectedIds = JSON.parse(localStorage.getItem(storageKey) || "[]");

        const wantShow = selectedIds.length > 0;
        const isVisible = bar.classList.contains('is-visible');

        if (wantShow && !isVisible) {
            bar.style.display = 'block';
            requestAnimationFrame(() => {
                bar.classList.remove('is-hiding');
                bar.classList.add('is-visible');
            });
        } else if (!wantShow && isVisible) {
            bar.classList.remove('is-visible');
            bar.classList.add('is-hiding');
            setTimeout(() => {
                bar.style.display = 'none';
                bar.classList.remove('is-hiding');
            }, ANIM_MS);
        } else if (!wantShow) {
            bar.style.display = 'none';
            bar.classList.remove('is-visible', 'is-hiding');
        }

        const bulkCount = bar.querySelector(".bulk-selected-count");
        if (bulkCount) bulkCount.textContent = selectedIds.length;

        bar.querySelectorAll('input[type="hidden"][name="selected[]"]').forEach(n => n.remove());
        selectedIds.forEach(id => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "selected[]";
            input.value = String(id);
            bar.appendChild(input);
        });

        // Ensure icon-only buttons expose a tooltip
        bar.querySelectorAll('.btn').forEach(btn => {
            const label = (btn.getAttribute('aria-label') || btn.textContent || '').trim();
            if (label && !btn.getAttribute('aria-label')) {
                btn.setAttribute('aria-label', label);
            }
            if (label && !btn.getAttribute('data-tooltip')) {
                btn.setAttribute('data-tooltip', label);
            }
        });
    });
}

function clearSelectionByTableId(tableId) {
    if (!tableId) return;
    const storageKey = `tableSelection-${tableId}`;
    try { localStorage.removeItem(storageKey); } catch (e) {}
    const table = document.getElementById(tableId);
    if (table) {
        table.querySelectorAll('tbody input[name="selected[]"]').forEach(cb => {
            cb.checked = false;
            const tr = cb.closest('tr');
            if (tr) tr.classList.remove('row-selected');
        });
        const selectAll = document.querySelector(`#select-all-${tableId}`);
        if (selectAll) selectAll.checked = false;
    }
    const bar = document.getElementById(`bulk-actions-${tableId}`);
    if (bar) {
        bar.querySelectorAll('input[type="hidden"][name="selected[]"]').forEach(n => n.remove());
        const bulkCount = bar.querySelector('.bulk-selected-count');
        if (bulkCount) bulkCount.textContent = '0';
        bar.style.display = 'none';
        bar.classList.remove('is-visible', 'is-hiding');
    }
}

const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1 && node.matches('table[id]')) {
                initSelectionForTable(node);
            }
            if (node.nodeType === 1 && node.querySelector('table[id]')) {
                node.querySelectorAll('table[id]').forEach(initSelectionForTable);
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('table[id]').forEach(initSelectionForTable);
    updateAllBulkBars();
    observer.observe(document.body, { childList: true, subtree: true });
});

document.addEventListener('click', (e) => {
    const bar = e.target.closest('div[data-bulk-table]');
    if (!bar) return;
    const isBulkActionBtn = e.target.closest('[hx-post], [hx-get], [hx-put], [hx-delete], [yoyo\\:post], button, a');
    if (!isBulkActionBtn) return;
    const tableId = bar.dataset.bulkTable;
    if (!tableId) return;
    window.__flute_pending_bulk_clear = tableId;
});

document.addEventListener('htmx:afterRequest', () => {
    if (window.__flute_pending_bulk_clear) {
        clearSelectionByTableId(window.__flute_pending_bulk_clear);
        window.__flute_pending_bulk_clear = null;
    }
});

document.addEventListener("htmx:afterSettle", (evt) => {
    updateAllBulkBars();

    const target = evt.detail.target;
    if (target && target.id === 'main') {
        const currentPath = window.location.pathname;
        if (window.__flute_last_pathname !== currentPath) {
            Object.keys(localStorage).forEach((k) => {
                if (k.startsWith("tableSelection-")) {
                    localStorage.removeItem(k);
                }
            });
            updateAllBulkBars();
        }
        window.__flute_last_pathname = currentPath;
    }
});
