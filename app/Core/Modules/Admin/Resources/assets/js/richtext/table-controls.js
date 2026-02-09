window.FluteRichText = window.FluteRichText || {};

/**
 * Notion-like table controls — overlay-based.
 *
 * Overlay appears when:
 *  1. Editor cursor is inside a table cell (selection-based)
 *  2. Mouse hovers over a table (hover-based)
 *
 * Features:
 *  - Column grip handles (top) — click opens dropdown menu
 *  - Row grip handles (left) — click opens dropdown menu
 *  - "+" buttons on right / bottom edges to add columns / rows
 *  - Right-click context menu on table cells
 */
window.FluteRichText.TableControls = class {
    constructor(editor, wrapper) {
        this.editor = editor;
        this.wrapper = wrapper;
        this.contentArea = wrapper.querySelector('.tiptap-content-area');
        this._overlay = null;
        this._contextMenu = null;
        this._gripDropdown = null;
        this._visible = false;
        this._hoverTW = null;
        this._selTW = null;

        this._boundUpdate = this._scheduleUpdate.bind(this);
        this._boundHideMenus = this._onGlobalDown.bind(this);
        this._boundCtxMenu = this._onContextMenu.bind(this);
        this._boundMouseMove = this._onMouseMove.bind(this);
        this._boundMouseLeave = this._onMouseLeave.bind(this);
        this._boundScroll = this._scheduleUpdate.bind(this);
        this._boundKeydown = this._onKeydown.bind(this);

        this._createOverlay();
        this._bindEvents();
        this._scheduleUpdate();
    }

    /* ---- helpers ---- */

    _t(key, fallback) {
        var i18n = window.FluteRichText && window.FluteRichText.i18n;
        return (i18n && i18n[key]) || fallback || key;
    }

    /* ---- find tableWrapper from editor selection ---- */

    _selectionTW() {
        try {
            var state = this.editor.state;
            var sel = state.selection;
            var pos = sel.$from || sel.$anchor;
            if (!pos) return null;
            var dom = this.editor.view.domAtPos(pos.pos);
            var node = dom.node;
            if (node.nodeType === 3) node = node.parentElement;
            return node ? node.closest('.tableWrapper') : null;
        } catch (e) {
            return null;
        }
    }

    /* ---- active table wrapper (selection OR hover) ---- */

    _activeTW() {
        return this._selectionTW() || this._hoverTW || null;
    }

    /* ---- overlay container ---- */

    _createOverlay() {
        var el = document.createElement('div');
        el.className = 'table-controls-overlay';
        var target = this.contentArea || this.wrapper;
        target.appendChild(el);
        this._overlay = el;
    }

    /* ---- events ---- */

    _bindEvents() {
        this.editor.on('update', this._boundUpdate);
        this.editor.on('selectionUpdate', this._boundUpdate);

        var area = this.contentArea;
        if (area) {
            area.addEventListener('mousemove', this._boundMouseMove);
            area.addEventListener('mouseleave', this._boundMouseLeave);
        }

        var pm = this.wrapper.querySelector('.ProseMirror');
        if (pm) {
            pm.addEventListener('contextmenu', this._boundCtxMenu);
        }

        if (this.contentArea) {
            this.contentArea.addEventListener('scroll', this._boundScroll);
        }

        document.addEventListener('mousedown', this._boundHideMenus);
        document.addEventListener('keydown', this._boundKeydown);
    }

    /* ---- hover tracking ---- */

    _onMouseMove(e) {
        clearTimeout(this._leaveTimer);

        var target = e.target;

        // Over overlay element — keep current state
        if (this._overlay && this._overlay.contains(target)) return;
        // Over dropdown — keep current state
        if (this._gripDropdown && this._gripDropdown.contains(target)) return;
        if (this._contextMenu && this._contextMenu.contains(target)) return;

        var tw = target.closest && target.closest('.tableWrapper');
        if (tw !== this._hoverTW) {
            this._hoverTW = tw || null;
            this._doUpdate();
        }
    }

    _onMouseLeave() {
        var self = this;
        clearTimeout(this._leaveTimer);
        this._leaveTimer = setTimeout(function () {
            self._hoverTW = null;
            self._doUpdate();
        }, 150);
    }

    /* ---- global mousedown — hide menus ---- */

    _onGlobalDown(e) {
        if (this._contextMenu && !this._contextMenu.contains(e.target)) {
            this._hideContextMenu();
        }
        if (this._gripDropdown && !this._gripDropdown.contains(e.target)) {
            // Check if click is on a grip button itself (will open new dropdown)
            var isGrip = e.target.closest && (
                e.target.closest('.table-ctrl-col-grip') ||
                e.target.closest('.table-ctrl-row-grip')
            );
            if (!isGrip) {
                this._hideGripDropdown();
            }
        }
    }

    _onKeydown(e) {
        if (e.key === 'Escape') {
            this._hideContextMenu();
            this._hideGripDropdown();
        }
    }

    /* ---- update scheduling (debounced rAF) ---- */

    _scheduleUpdate() {
        if (this._raf) return;
        var self = this;
        this._raf = requestAnimationFrame(function () {
            self._raf = null;
            self._doUpdate();
        });
    }

    /* ---- main render ---- */

    _doUpdate() {
        if (!this._overlay) return;
        this._overlay.innerHTML = '';

        var tw = this._activeTW();
        if (!tw) {
            this._overlay.style.display = 'none';
            this._visible = false;
            return;
        }

        var table = tw.querySelector('table');
        if (!table) {
            this._overlay.style.display = 'none';
            this._visible = false;
            return;
        }

        var refEl = this.contentArea || this.wrapper;
        var refRect = refEl.getBoundingClientRect();
        var tRect = table.getBoundingClientRect();

        var pad = 4;
        var addBtnW = 22;
        var addBtnH = 24;
        var gripH = 16;
        var gripW = 16;

        var oLeft = tRect.left - refRect.left + refEl.scrollLeft - gripW;
        var oTop  = tRect.top  - refRect.top  + refEl.scrollTop  - gripH;
        var oWidth  = gripW + tRect.width + pad + addBtnW;
        var oHeight = gripH + tRect.height + pad + addBtnH;

        var ov = this._overlay;
        ov.style.display = 'block';
        ov.style.left   = oLeft + 'px';
        ov.style.top    = oTop  + 'px';
        ov.style.width  = oWidth + 'px';
        ov.style.height = oHeight + 'px';

        this._renderColumnGrips(table, tRect, gripW, gripH);
        this._renderRowGrips(table, tRect, gripW, gripH);
        this._renderAddColumnBtn(table, tRect, gripW, gripH, pad);
        this._renderAddRowBtn(table, tRect, gripW, gripH, pad);

        this._visible = true;
    }

    /* ---- column grips (top) — click opens dropdown ---- */

    _renderColumnGrips(table, tRect, gW, gH) {
        var self = this;
        var firstRow = table.querySelector('tr');
        if (!firstRow) return;

        var cells = firstRow.querySelectorAll('th, td');
        cells.forEach(function (cell, idx) {
            var cRect = cell.getBoundingClientRect();
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'table-ctrl-col-grip';
            btn.style.left   = (gW + cRect.left - tRect.left) + 'px';
            btn.style.top    = '0';
            btn.style.width  = cRect.width + 'px';
            btn.style.height = gH + 'px';
            btn.innerHTML = '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/></svg>';

            btn.addEventListener('mousedown', function (e) { e.preventDefault(); e.stopPropagation(); });
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self._focusCellAt(cell);
                self._showColumnDropdown(btn, cell);
            });

            self._overlay.appendChild(btn);
        });
    }

    /* ---- row grips (left) — click opens dropdown ---- */

    _renderRowGrips(table, tRect, gW, gH) {
        var self = this;
        var rows = table.querySelectorAll('tr');
        rows.forEach(function (row, idx) {
            var rRect = row.getBoundingClientRect();
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'table-ctrl-row-grip';
            btn.style.left   = '0';
            btn.style.top    = (gH + rRect.top - tRect.top) + 'px';
            btn.style.width  = gW + 'px';
            btn.style.height = rRect.height + 'px';
            btn.innerHTML = '<svg viewBox="0 0 16 16" fill="currentColor"><circle cx="8" cy="5" r="1.5"/><circle cx="8" cy="11" r="1.5"/></svg>';

            btn.addEventListener('mousedown', function (e) { e.preventDefault(); e.stopPropagation(); });
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var firstCell = row.querySelector('td, th');
                if (firstCell) self._focusCellAt(firstCell);
                self._showRowDropdown(btn, row);
            });

            self._overlay.appendChild(btn);
        });
    }

    /* ---- "+" add column button (right edge) ---- */

    _renderAddColumnBtn(table, tRect, gW, gH, pad) {
        var self = this;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'table-ctrl table-ctrl-add-col';
        btn.setAttribute('data-tooltip', this._t('add_column', 'Add column'));
        btn.innerHTML = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>';

        btn.style.left   = (gW + tRect.width + pad) + 'px';
        btn.style.top    = gH + 'px';
        btn.style.height = tRect.height + 'px';
        btn.style.width  = '20px';

        btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            self._focusLastCell(table, 'col');
            setTimeout(function () {
                self.editor.chain().focus().addColumnAfter().run();
            }, 0);
        });

        this._overlay.appendChild(btn);
    }

    /* ---- "+" add row button (bottom edge) ---- */

    _renderAddRowBtn(table, tRect, gW, gH, pad) {
        var self = this;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'table-ctrl table-ctrl-add-row';
        btn.setAttribute('data-tooltip', this._t('add_row', 'Add row'));
        btn.innerHTML = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>';

        btn.style.left   = gW + 'px';
        btn.style.top    = (gH + tRect.height + pad) + 'px';
        btn.style.width  = tRect.width + 'px';
        btn.style.height = '22px';

        btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            self._focusLastCell(table, 'row');
            setTimeout(function () {
                self.editor.chain().focus().addRowAfter().run();
            }, 0);
        });

        this._overlay.appendChild(btn);
    }

    /* ---- column grip dropdown ---- */

    _showColumnDropdown(gripBtn, cell) {
        this._hideGripDropdown();
        this._hideContextMenu();

        var self = this;
        var t = this._t.bind(this);

        var actions = [
            { label: t('insert_left', 'Insert left'), cmd: 'addColumnBefore' },
            { label: t('insert_right', 'Insert right'), cmd: 'addColumnAfter' },
            { type: 'separator' },
            { label: t('toggle_header_col', 'Toggle header column'), cmd: 'toggleHeaderColumn' },
            { type: 'separator' },
            { label: t('delete_column', 'Delete column'), cmd: 'deleteColumn', danger: true },
        ];

        this._gripDropdown = this._buildDropdown(actions, gripBtn);
    }

    /* ---- row grip dropdown ---- */

    _showRowDropdown(gripBtn, row) {
        this._hideGripDropdown();
        this._hideContextMenu();

        var self = this;
        var t = this._t.bind(this);

        var actions = [
            { label: t('insert_above', 'Insert above'), cmd: 'addRowBefore' },
            { label: t('insert_below', 'Insert below'), cmd: 'addRowAfter' },
            { type: 'separator' },
            { label: t('toggle_header_row', 'Toggle header row'), cmd: 'toggleHeaderRow' },
            { type: 'separator' },
            { label: t('delete_row', 'Delete row'), cmd: 'deleteRow', danger: true },
        ];

        this._gripDropdown = this._buildDropdown(actions, gripBtn);
    }

    /* ---- build dropdown (reused for col/row grips) ---- */

    _buildDropdown(actions, anchorBtn) {
        var self = this;
        var menu = document.createElement('div');
        menu.className = 'table-context-menu';

        actions.forEach(function (action) {
            if (action.type === 'separator') {
                var sep = document.createElement('div');
                sep.className = 'table-ctx-sep';
                menu.appendChild(sep);
                return;
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'table-ctx-item' + (action.danger ? ' table-ctx-danger' : '');
            btn.textContent = action.label;

            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var chain = self.editor.chain().focus();
                if (typeof chain[action.cmd] === 'function') {
                    chain[action.cmd]().run();
                }
                self._hideGripDropdown();
            });

            menu.appendChild(btn);
        });

        document.body.appendChild(menu);

        // Position below the anchor button
        var aRect = anchorBtn.getBoundingClientRect();
        requestAnimationFrame(function () {
            if (!menu.parentNode) return;
            var mRect = menu.getBoundingClientRect();
            var vw = window.innerWidth;
            var vh = window.innerHeight;

            var left = aRect.left;
            var top = aRect.bottom + 4;

            if (left + mRect.width > vw - 8) left = vw - mRect.width - 8;
            if (top + mRect.height > vh - 8) top = aRect.top - mRect.height - 4;
            if (left < 8) left = 8;
            if (top < 8) top = 8;

            menu.style.left = left + 'px';
            menu.style.top  = top + 'px';
        });

        return menu;
    }

    _hideGripDropdown() {
        if (!this._gripDropdown) return;
        this._gripDropdown.remove();
        this._gripDropdown = null;
    }

    /* ---- cell focus helpers ---- */

    _focusCellAt(cell) {
        try {
            var pos = this.editor.view.posAtDOM(cell, 0);
            if (pos != null) {
                this.editor.chain().focus().setTextSelection(pos).run();
            }
        } catch (e) { /* ignore */ }
    }

    _focusLastCell(table, direction) {
        var rows = table.querySelectorAll('tr');
        if (!rows.length) return;
        var cell;
        if (direction === 'row') {
            cell = rows[rows.length - 1].querySelector('td, th');
        } else {
            var lastRow = rows[rows.length - 1];
            var cells = lastRow.querySelectorAll('td, th');
            cell = cells[cells.length - 1];
        }
        if (cell) this._focusCellAt(cell);
    }

    /* ---- right-click context menu ---- */

    _onContextMenu(e) {
        var cell = e.target.closest && e.target.closest('td, th');
        if (!cell) return;
        if (!cell.closest('.tableWrapper')) return;

        e.preventDefault();
        e.stopPropagation();
        this._focusCellAt(cell);
        this._hideGripDropdown();
        this._showContextMenu(e.clientX, e.clientY);
    }

    _showContextMenu(x, y) {
        this._hideContextMenu();

        var self = this;
        var t = this._t.bind(this);
        var menu = document.createElement('div');
        menu.className = 'table-context-menu';

        var actions = [
            { label: t('insert_left', 'Insert left'), cmd: 'addColumnBefore' },
            { label: t('insert_right', 'Insert right'), cmd: 'addColumnAfter' },
            { label: t('insert_above', 'Insert above'), cmd: 'addRowBefore' },
            { label: t('insert_below', 'Insert below'), cmd: 'addRowAfter' },
            { type: 'separator' },
            { label: t('delete_column', 'Delete column'), cmd: 'deleteColumn', danger: true },
            { label: t('delete_row', 'Delete row'), cmd: 'deleteRow', danger: true },
            { type: 'separator' },
            { label: t('merge_cells', 'Merge cells'), cmd: 'mergeCells' },
            { label: t('toggle_header_row', 'Toggle header row'), cmd: 'toggleHeaderRow' },
            { label: t('toggle_header_col', 'Toggle header column'), cmd: 'toggleHeaderColumn' },
            { type: 'separator' },
            { label: t('delete_table', 'Delete table'), cmd: 'deleteTable', danger: true },
        ];

        actions.forEach(function (action) {
            if (action.type === 'separator') {
                var sep = document.createElement('div');
                sep.className = 'table-ctx-sep';
                menu.appendChild(sep);
                return;
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'table-ctx-item' + (action.danger ? ' table-ctx-danger' : '');
            btn.textContent = action.label;

            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var chain = self.editor.chain().focus();
                if (typeof chain[action.cmd] === 'function') {
                    chain[action.cmd]().run();
                }
                self._hideContextMenu();
            });

            menu.appendChild(btn);
        });

        document.body.appendChild(menu);
        this._contextMenu = menu;

        requestAnimationFrame(function () {
            if (!self._contextMenu) return;
            var rect = menu.getBoundingClientRect();
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            var left = x, top = y;
            if (left + rect.width > vw - 8)  left = vw - rect.width - 8;
            if (top + rect.height > vh - 8)  top  = vh - rect.height - 8;
            if (left < 8) left = 8;
            if (top  < 8) top  = 8;
            menu.style.left = left + 'px';
            menu.style.top  = top + 'px';
        });
    }

    _hideContextMenu() {
        if (!this._contextMenu) return;
        this._contextMenu.remove();
        this._contextMenu = null;
    }

    /* ---- cleanup ---- */

    destroy() {
        if (this._raf) cancelAnimationFrame(this._raf);
        clearTimeout(this._leaveTimer);
        this._hideContextMenu();
        this._hideGripDropdown();

        this.editor.off('update', this._boundUpdate);
        this.editor.off('selectionUpdate', this._boundUpdate);

        var area = this.contentArea;
        if (area) {
            area.removeEventListener('mousemove', this._boundMouseMove);
            area.removeEventListener('mouseleave', this._boundMouseLeave);
            area.removeEventListener('scroll', this._boundScroll);
        }

        var pm = this.wrapper.querySelector('.ProseMirror');
        if (pm) {
            pm.removeEventListener('contextmenu', this._boundCtxMenu);
        }

        document.removeEventListener('mousedown', this._boundHideMenus);
        document.removeEventListener('keydown', this._boundKeydown);

        if (this._overlay && this._overlay.parentNode) {
            this._overlay.remove();
        }
        this._overlay = null;
    }
};
