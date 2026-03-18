/**
 * VisibilityConditions — unified widget visibility panel.
 *
 * Merges all visibility-related settings into one editor:
 *  - Auth state (all / guests / authorized)
 *  - Roles (multi-select chips)
 *  - Device type (all / desktop / tablet / mobile) — 2×2 grid
 *  - Excluded paths (global scope only)
 *
 * Also manages the topbar preview mode (device + auth toggles).
 */
class VisibilityConditions {
    constructor(editor) {
        this.editor = editor;
        this.config = editor.config;
        this.eventBus = window.FlutePageEdit.eventBus;
        this.utils = window.FlutePageEdit.utils;

        this.previewAuth = 'all';
        this.previewDevice = 'all';

        this.deviceWidths = { all: null, desktop: null, tablet: 768, mobile: 375 };

        // Cached roles list (fetched once)
        this._rolesCache = null;

        this._t = (key, fallback) => typeof translate === 'function' ? translate(key) : fallback;
    }

    // ═══════════════════════════════════════════════════════════════════
    // Init
    // ═══════════════════════════════════════════════════════════════════

    initialize() {
        this._setupPreviewControls();
        this._setupToolbarButtons();
        this._setupAutoConditions();
    }

    /**
     * Replace old separate buttons with one unified "visibility" button.
     */
    _setupToolbarButtons() {
        const iconsTpl = document.getElementById('widget-toolbar-icons');
        const conditionsIcon = iconsTpl?.querySelector('[data-icon="conditions"]')?.innerHTML || '';

        // Remove old excludedPaths button — it's now part of the visibility panel
        this.editor.widgetToolbar.removeBaseButton('excludedPaths');

        this.editor.widgetToolbar.addBaseButton('conditions', {
            icon: conditionsIcon,
            tooltipKey: 'page-edit.conditions',
            order: 25,
            onClick: (widgetEl) => this.openConditionsEditor(widgetEl),
            shouldShow: (widgetEl) => widgetEl.getAttribute('data-widget-name') !== 'Content'
        });
    }

    _setupAutoConditions() {
        this.eventBus.on(window.FlutePageEdit.events.WIDGET_DROPPED, (data) => {
            if (!data?.widget) return;
            if (data.widget.getAttribute('data-widget-name') === 'Content') return;
            if (this.previewAuth === 'all' && this.previewDevice === 'all') return;

            const conditions = {
                auth: this.previewAuth !== 'all' ? this.previewAuth : 'all',
                device: this.previewDevice !== 'all' ? this.previewDevice : 'all',
                roles: []
            };
            this.setConditions(data.widget, conditions);
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // Preview controls (topbar)
    // ═══════════════════════════════════════════════════════════════════

    _setupPreviewControls() {
        document.querySelectorAll('.pe-preview-auth__btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.authPreview;
                if (mode === this.previewAuth) return;
                this.previewAuth = mode;
                document.querySelectorAll('.pe-preview-auth__btn').forEach(b =>
                    b.classList.toggle('active', b.dataset.authPreview === mode));
                this._applyPreview();
            });
        });

        document.querySelectorAll('.pe-preview-device__btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.devicePreview;
                if (mode === this.previewDevice) return;
                this.previewDevice = mode;
                document.querySelectorAll('.pe-preview-device__btn').forEach(b =>
                    b.classList.toggle('active', b.dataset.devicePreview === mode));
                this._applyDevicePreview(mode);
                this._applyPreview();
            });
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // Preview — hide + recompact for real layout preview
    // ═══════════════════════════════════════════════════════════════════

    _applyPreview() {
        const isFiltered = this.previewAuth !== 'all' || this.previewDevice !== 'all';
        const gs = this.editor.gridController?.gsGrid;
        const items = this.editor.gridController?.getItems() || [];

        if (!gs) return;

        // Save original positions before first filter (for restoring later)
        if (isFiltered && !this._savedPositions) {
            this._savedPositions = new Map();
            items.forEach(el => {
                const node = el.gridstackNode;
                if (node) {
                    this._savedPositions.set(el, { x: node.x, y: node.y, w: node.w, h: node.h });
                }
            });
        }

        gs.batchUpdate();

        items.forEach(el => {
            if (el.getAttribute('data-widget-name') === 'Content') return;

            const conditions = this.getConditions(el);

            if (!isFiltered || this._matchesPreview(conditions)) {
                // Show widget
                if (el.classList.contains('pe-condition-hidden')) {
                    el.classList.remove('pe-condition-hidden');
                    el.style.removeProperty('display');
                    gs.update(el, { w: el._peOrigW || (el.gridstackNode?.w ?? 6) });
                }
            } else {
                // Hide widget — save original width, collapse to 0
                if (!el.classList.contains('pe-condition-hidden')) {
                    el._peOrigW = el.gridstackNode?.w ?? 6;
                    el.classList.add('pe-condition-hidden');
                    el.style.display = 'none';
                }
            }
        });

        gs.batchUpdate(false);

        // Recompact after hiding
        gs.compact();

        // Restore all positions when filter is removed
        if (!isFiltered && this._savedPositions) {
            gs.batchUpdate();
            this._savedPositions.forEach((pos, el) => {
                if (document.contains(el)) {
                    el.classList.remove('pe-condition-hidden');
                    el.style.removeProperty('display');
                    gs.update(el, pos);
                }
            });
            gs.batchUpdate(false);
            this._savedPositions = null;
        }

        this.eventBus.emit(window.FlutePageEdit.events.PREVIEW_CHANGED, {
            auth: this.previewAuth, device: this.previewDevice
        });
    }

    _applyDevicePreview(mode) {
        const grid = document.getElementById('widget-grid');
        if (!grid) return;
        const container = grid.closest('.container, .container-fullwidth');
        if (!container) return;

        document.body.classList.remove('pe-device-desktop', 'pe-device-tablet', 'pe-device-mobile');
        const width = this.deviceWidths[mode];

        if (width) {
            document.body.classList.add('pe-device-' + mode);
            container.style.maxWidth = width + 'px';
            container.style.margin = '0 auto';
            container.style.transition = 'max-width 0.35s cubic-bezier(0.22, 1, 0.36, 1)';
        } else {
            container.style.removeProperty('max-width');
            container.style.removeProperty('margin');
            container.style.removeProperty('transition');
        }

        setTimeout(() => this.editor.gridController?.resizeAllToContent(), 400);
    }

    _matchesPreview(conditions) {
        if (this.previewAuth !== 'all' && conditions.auth !== 'all' && conditions.auth !== this.previewAuth) {
            return false;
        }
        if (this.previewDevice !== 'all' && conditions.device !== 'all' && conditions.device !== this.previewDevice) {
            return false;
        }
        return true;
    }

    // ═══════════════════════════════════════════════════════════════════
    // Conditions data
    // ═══════════════════════════════════════════════════════════════════

    getConditions(widgetEl) {
        try {
            const raw = widgetEl.dataset.conditions;
            if (raw) {
                const parsed = JSON.parse(raw);
                if (!parsed.roles) parsed.roles = [];
                return parsed;
            }
        } catch (_) {}
        return { auth: 'all', device: 'all', roles: [] };
    }

    setConditions(widgetEl, conditions) {
        widgetEl.dataset.conditions = JSON.stringify(conditions);
        this._updateConditionsBadge(widgetEl);
        this._applyPreview();
    }

    /**
     * Check if conditions have any non-default values.
     */
    _hasConditions(conditions) {
        return conditions.auth !== 'all'
            || conditions.device !== 'all'
            || (conditions.roles && conditions.roles.length > 0);
    }

    /**
     * Build human-readable parts for badge / overlay.
     */
    _conditionParts(conditions) {
        const parts = [];
        if (conditions.auth !== 'all') {
            parts.push(conditions.auth === 'guest'
                ? this._t('page-edit.cond_guests', 'Guests')
                : this._t('page-edit.cond_auth', 'Auth'));
        }
        if (conditions.roles && conditions.roles.length > 0) {
            // Show count instead of names to keep badge small
            const rolesLabel = this._t('page-edit.cond_roles_count', '{n} roles')
                .replace('{n}', conditions.roles.length);
            parts.push(conditions.roles.length + ' ' + (this._t('page-edit.cond_roles_short', 'roles')));
        }
        if (conditions.device !== 'all') {
            const map = {
                desktop: this._t('page-edit.cond_desktop', 'PC'),
                tablet: this._t('page-edit.cond_tablet', 'Tablet'),
                mobile: this._t('page-edit.cond_mobile', 'Mobile')
            };
            parts.push(map[conditions.device] || conditions.device);
        }
        return parts;
    }

    // ═══════════════════════════════════════════════════════════════════
    // Badge
    // ═══════════════════════════════════════════════════════════════════

    _updateConditionsBadge(widgetEl) {
        let badge = widgetEl.querySelector('.pe-conditions-badge');
        const conditions = this.getConditions(widgetEl);

        if (this._hasConditions(conditions)) {
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'pe-conditions-badge';
                widgetEl.appendChild(badge);
            }
            badge.textContent = this._conditionParts(conditions).join(' · ');
        } else {
            badge?.remove();
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Roles fetching
    // ═══════════════════════════════════════════════════════════════════

    async _fetchRoles() {
        if (this._rolesCache) return this._rolesCache;

        try {
            const params = new URLSearchParams({ entity: 'roles', query: '', page: '1' });
            const res = await this.utils.csrfFetch(u('admin/select/search?' + params.toString()), {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            if (!res.ok) return [];
            const data = await res.json();
            this._rolesCache = Array.isArray(data) ? data : [];
            return this._rolesCache;
        } catch (err) {
            this.utils.logError('fetchRoles', err);
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Editor HTML builder
    // ═══════════════════════════════════════════════════════════════════

    _icon(name) {
        const el = document.querySelector(`#pe-conditions-icons [data-icon="${name}"]`);
        return el ? el.innerHTML : '';
    }

    _buildEditorHTML(conditions, excludedPaths, isGlobal) {
        const t = this._t;
        const ic = (name) => this._icon(name);

        const authBtn = (value, icon, labelKey, fallback) => {
            const active = conditions.auth === value ? ' active' : '';
            return `<button type="button" class="pe-cond-toggle__btn${active}" data-group="auth" data-value="${value}">
                ${ic(icon)}<span>${t(labelKey, fallback)}</span>
            </button>`;
        };

        const deviceBtn = (value, icon, labelKey, fallback) => {
            const active = conditions.device === value ? ' active' : '';
            return `<button type="button" class="pe-cond-toggle__btn${active}" data-group="device" data-value="${value}">
                ${ic(icon)}<span>${t(labelKey, fallback)}</span>
            </button>`;
        };

        // Excluded paths section (global scope only)
        let excludedPathsHTML = '';
        if (isGlobal) {
            const tagsHTML = excludedPaths.map(p =>
                `<div class="pe-excluded-paths__tag">
                    <span class="pe-excluded-paths__tag-text">${this._escapeHtml(p)}</span>
                    <button type="button" class="pe-excluded-paths__tag-remove">&times;</button>
                </div>`
            ).join('');

            excludedPathsHTML = `
            <div class="pe-conditions-editor__divider"></div>
            <div class="pe-conditions-editor__section">
                <label class="pe-conditions-editor__label">
                    ${ic('eye-slash')} ${t('page-edit.excluded_paths', 'Hidden paths')}
                </label>
                <p class="pe-conditions-editor__hint">${t('page-edit.excluded_paths_desc', 'Widget won\'t show on these paths')}</p>
                <div class="pe-excluded-paths__list" id="pe-cond-excluded-list">${tagsHTML}</div>
                <div class="pe-excluded-paths__add">
                    <input type="text" class="pe-excluded-paths__input" id="pe-cond-excluded-input"
                        placeholder="${t('page-edit.excluded_paths_placeholder', '/path')}"
                        autocomplete="off" spellcheck="false" />
                    <button type="button" class="pe-excluded-paths__add-btn" id="pe-cond-excluded-add">
                        ${t('page-edit.add_path', 'Add')}
                    </button>
                </div>
                <p class="pe-conditions-editor__hint">${t('page-edit.excluded_paths_hint', 'Examples: /about, /user/*, /news/**')}</p>
            </div>`;
        }

        return `<div class="pe-conditions-editor">
            <div class="pe-conditions-editor__header">
                <h4 class="pe-conditions-editor__title">${t('page-edit.conditions', 'Visibility')}</h4>
                <p class="pe-conditions-editor__desc">${t('page-edit.conditions_desc', 'Set conditions for this widget.')}</p>
            </div>

            <div class="pe-conditions-editor__section">
                <label class="pe-conditions-editor__label">
                    ${ic('user')} ${t('page-edit.cond_auth_label', 'User state')}
                </label>
                <div class="pe-cond-toggle pe-cond-toggle--3">
                    ${authBtn('all', 'users-three', 'page-edit.auth_all', 'Everyone')}
                    ${authBtn('guest', 'user-minus', 'page-edit.cond_guests', 'Guests')}
                    ${authBtn('auth', 'user-check', 'page-edit.cond_auth', 'Authorized')}
                </div>
            </div>

            <div class="pe-conditions-editor__section">
                <label class="pe-conditions-editor__label">
                    ${ic('shield')} ${t('page-edit.cond_roles_label', 'Roles')}
                </label>
                <p class="pe-conditions-editor__hint">${t('page-edit.cond_roles_hint', 'Leave empty to show for all roles')}</p>
                <div class="pe-cond-roles" id="pe-cond-roles-container">
                    <div class="pe-cond-roles__loading">${t('def.loading', 'Loading...')}</div>
                </div>
            </div>

            <div class="pe-conditions-editor__divider"></div>

            <div class="pe-conditions-editor__section">
                <label class="pe-conditions-editor__label">
                    ${ic('devices')} ${t('page-edit.cond_device_label', 'Device type')}
                </label>
                <div class="pe-cond-toggle pe-cond-toggle--2x2">
                    ${deviceBtn('all', 'browsers', 'page-edit.device_all', 'All')}
                    ${deviceBtn('desktop', 'desktop', 'page-edit.cond_desktop', 'Desktop')}
                    ${deviceBtn('tablet', 'tablet', 'page-edit.cond_tablet', 'Tablet')}
                    ${deviceBtn('mobile', 'mobile', 'page-edit.cond_mobile', 'Mobile')}
                </div>
            </div>

            ${excludedPathsHTML}
        </div>`;
    }

    _escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ═══════════════════════════════════════════════════════════════════
    // Open unified editor
    // ═══════════════════════════════════════════════════════════════════

    async openConditionsEditor(widgetEl) {
        const widgetName = widgetEl.getAttribute('data-widget-name');
        if (!widgetName || widgetName === 'Content') return;

        const rightSidebar = document.getElementById('page-edit-dialog');
        const sidebarContent = document.getElementById('page-edit-dialog-content');
        if (!rightSidebar || !sidebarContent) return;

        if (!this.editor.rightSidebarDialog) {
            this.editor.rightSidebarDialog = new A11yDialog(rightSidebar);
            this.editor.rightSidebarDialog.on('hide', () => { window.currentEditedWidgetEl = null; });
        }

        const onHide = () => {
            this._cleanupConditionsEditor();
            this.editor.rightSidebarDialog.off('hide', onHide);
        };
        this.editor.rightSidebarDialog.on('hide', onHide);

        const conditions = this.getConditions(widgetEl);
        const isGlobal = this.editor.scope === 'global';

        let excludedPaths = [];
        if (isGlobal) {
            try { excludedPaths = JSON.parse(widgetEl.dataset.excludedPaths || '[]'); } catch (_) {}
        }

        sidebarContent.innerHTML = this._buildEditorHTML(conditions, excludedPaths, isGlobal);

        // ── Toggle buttons (auth + device) ────────────────────────────
        sidebarContent.querySelectorAll('.pe-cond-toggle').forEach(group => {
            group.querySelectorAll('.pe-cond-toggle__btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    group.querySelectorAll('.pe-cond-toggle__btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                });
            });
        });

        // ── Roles — async load ────────────────────────────────────────
        this._loadRolesUI(sidebarContent, conditions.roles || []);

        // ── Excluded paths (global only) ──────────────────────────────
        if (isGlobal) {
            this._setupExcludedPathsUI(sidebarContent);
        }

        // ── Save button ───────────────────────────────────────────────
        const saveBtn = document.getElementById('widget-settings-save-btn');
        if (saveBtn) {
            saveBtn.removeAttribute('hx-post');
            saveBtn.removeAttribute('hx-vals');
            htmx.process(saveBtn);

            this._condSaveHandler = () => {
                const activeAuth = sidebarContent.querySelector('.pe-cond-toggle__btn[data-group="auth"].active');
                const activeDevice = sidebarContent.querySelector('.pe-cond-toggle__btn[data-group="device"].active');

                // Gather selected roles
                const selectedRoles = [];
                sidebarContent.querySelectorAll('.pe-cond-role__chip.active').forEach(chip => {
                    selectedRoles.push(parseInt(chip.dataset.roleId));
                });

                const newConditions = {
                    auth: activeAuth?.dataset.value || 'all',
                    device: activeDevice?.dataset.value || 'all',
                    roles: selectedRoles
                };

                this.setConditions(widgetEl, newConditions);

                // Save excluded paths if global
                if (isGlobal) {
                    const list = sidebarContent.querySelector('#pe-cond-excluded-list');
                    const paths = list ? Array.from(list.querySelectorAll('.pe-excluded-paths__tag-text'))
                        .map(el => el.textContent.trim()).filter(Boolean) : [];
                    widgetEl.dataset.excludedPaths = JSON.stringify(paths);
                }

                this.editor.hasUnsavedChanges = true;
                this.editor.updateSaveButtonState();
                this.editor.history?.push();
                this.editor.saveToLocalStorage();
                if (this.editor.rightSidebarDialog) this.editor.rightSidebarDialog.hide();
            };
            saveBtn.addEventListener('click', this._condSaveHandler);
        }

        this.editor.rightSidebarDialog.show();
    }

    /**
     * Load roles from API and render as toggle chips.
     */
    async _loadRolesUI(container, selectedRoleIds) {
        const rolesContainer = container.querySelector('#pe-cond-roles-container');
        if (!rolesContainer) return;

        const roles = await this._fetchRoles();

        if (!roles.length) {
            rolesContainer.innerHTML = `<p class="pe-conditions-editor__hint">${this._t('page-edit.cond_no_roles', 'No roles available')}</p>`;
            return;
        }

        rolesContainer.innerHTML = roles.map(role => {
            const active = selectedRoleIds.includes(role.value) ? ' active' : '';
            const colorDot = role.color
                ? `<span class="pe-cond-role__dot" style="background:${this._escapeHtml(role.color)}"></span>`
                : '';
            return `<button type="button" class="pe-cond-role__chip${active}" data-role-id="${role.value}">
                ${colorDot}<span>${this._escapeHtml(role.text)}</span>
            </button>`;
        }).join('');

        // Toggle on click (multi-select)
        rolesContainer.querySelectorAll('.pe-cond-role__chip').forEach(chip => {
            chip.addEventListener('click', () => chip.classList.toggle('active'));
        });
    }

    /**
     * Setup excluded paths add/remove UI.
     */
    _setupExcludedPathsUI(container) {
        const list = container.querySelector('#pe-cond-excluded-list');
        const input = container.querySelector('#pe-cond-excluded-input');
        const addBtn = container.querySelector('#pe-cond-excluded-add');
        if (!list || !input || !addBtn) return;

        const addPath = () => {
            const val = input.value.trim();
            if (!val) return;
            const existing = Array.from(list.querySelectorAll('.pe-excluded-paths__tag-text'))
                .map(el => el.textContent.trim());
            if (existing.includes(val)) { input.value = ''; return; }

            const tag = document.createElement('div');
            tag.className = 'pe-excluded-paths__tag';
            tag.innerHTML = `<span class="pe-excluded-paths__tag-text">${this._escapeHtml(val)}</span>
                <button type="button" class="pe-excluded-paths__tag-remove">&times;</button>`;
            list.appendChild(tag);
            input.value = '';
            input.focus();
        };

        addBtn.addEventListener('click', addPath);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); addPath(); }
        });

        list.addEventListener('click', (e) => {
            const btn = e.target.closest('.pe-excluded-paths__tag-remove');
            if (btn) btn.closest('.pe-excluded-paths__tag')?.remove();
        });
    }

    _cleanupConditionsEditor() {
        window.currentConditionsWidgetEl = null;
        const saveBtn = document.getElementById('widget-settings-save-btn');
        if (saveBtn && this._condSaveHandler) {
            saveBtn.removeEventListener('click', this._condSaveHandler);
            this._condSaveHandler = null;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Reset / refresh
    // ═══════════════════════════════════════════════════════════════════

    resetPreview() {
        this.previewAuth = 'all';
        this.previewDevice = 'all';

        document.querySelectorAll('.pe-preview-auth__btn').forEach(b =>
            b.classList.toggle('active', b.dataset.authPreview === 'all'));
        document.querySelectorAll('.pe-preview-device__btn').forEach(b =>
            b.classList.toggle('active', b.dataset.devicePreview === 'all'));

        this._applyDevicePreview('all');

        // Restore all widgets to visible
        const gs = this.editor.gridController?.gsGrid;
        if (gs && this._savedPositions) {
            gs.batchUpdate();
            this._savedPositions.forEach((pos, el) => {
                if (document.contains(el)) {
                    el.classList.remove('pe-condition-hidden');
                    el.style.removeProperty('display');
                    gs.update(el, pos);
                }
            });
            gs.batchUpdate(false);
            this._savedPositions = null;
        } else {
            const items = this.editor.gridController?.getItems() || [];
            items.forEach(el => {
                el.classList.remove('pe-condition-hidden');
                el.style.removeProperty('display');
            });
        }
    }

    refreshBadges() {
        const items = this.editor.gridController?.getItems() || [];
        items.forEach(el => this._updateConditionsBadge(el));
    }
}

window.FlutePageEdit.register('VisibilityConditions', VisibilityConditions);
