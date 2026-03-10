/**
 * Select component using Tom Select
 * @see https://tom-select.js.org/
 */
class Select {
    constructor() {
        this.instances = new Map();
        this.dropdownRepositionHandlers = new Map();
        this.init();
    }

    cleanup() {
        this.instances.forEach((instance, select) => {
            if (!document.body.contains(select) || this.isInstanceStale(select, instance)) {
                this.destroyInstance(select, instance);
            }
        });
    }

    init(root = document) {
        this.cleanup();

        this.getSelectElements(root).forEach((select) => {
            this.ensureNativeChangeListener(select);

            const existingInstance = this.instances.get(select);
            if (existingInstance) {
                if (this.isInstanceStale(select, existingInstance)) {
                    this.destroyInstance(select, existingInstance);
                } else {
                    this.applyWrapperState(select, existingInstance);
                    existingInstance.sync();
                    return;
                }
            }

            if (select.tomselect) {
                if (this.isInstanceStale(select, select.tomselect)) {
                    this.destroyInstance(select, select.tomselect);
                } else {
                    select.tomselect.sync();
                    this.instances.set(select, select.tomselect);
                    this.applyWrapperState(select, select.tomselect);
                    return;
                }
            }

            this.createInstance(select);
        });
    }

    createInstance(select) {
        const config = this.getConfig(select);
        const instance = new TomSelect(select, config);
        this.instances.set(select, instance);

        if (instance.wrapper) {
            instance.wrapper.style.width = '100%';
        }

        this.applyWrapperState(select, instance);
        this.setSearchPlaceholder(instance);
        instance.sync();
        this.applyInitialValue(select, instance);
        this.updateClearButton(instance);
        this.makeVisibleForYoyo(select);
        this.bindChangeHandler(select, instance);
        this.bindAsyncPreload(select, instance);
        this.bindDropdownPositioning(select, instance);
    }

    applyInitialValue(select, instance) {
        const initVal = select.dataset.initialValue;
        if (initVal === undefined || initVal === 'null' || initVal === '') return;

        try {
            const parsed = JSON.parse(initVal);
            if (parsed !== null) {
                instance.setValue(parsed, true);
            }
        } catch (e) {
            if (initVal) instance.setValue(initVal, true);
        }
    }

    makeVisibleForYoyo(select) {
        Object.assign(select.style, {
            display: 'block',
            position: 'absolute',
            top: '0',
            left: '0',
            opacity: '0',
            pointerEvents: 'none',
            width: '1px',
            height: '1px',
            zIndex: '-1',
        });
    }

    bindChangeHandler(select, instance) {
        instance.on('change', () => {
            if (instance.settings.mode === 'multi' && instance.items.includes('')) {
                instance.removeItem('');
            }

            this.updateClearButton(instance);

            if (select.dataset.yoyo) {
                const yoyoValue = select.dataset.yoyoValue;
                if (yoyoValue) {
                    instance.setValue(yoyoValue);
                }
            }

            if (select._changeTimeout) clearTimeout(select._changeTimeout);
            select._changeTimeout = setTimeout(() => {
                this.dispatchSyntheticChange(select);
            }, 100);
        });

        if (instance.settings.mode === 'multi' && instance.items.includes('')) {
            instance.removeItem('');
        }
    }

    updateClearButton(instance) {
        if (!instance.control) return;
        const btn = instance.control.querySelector(':scope > .clear-button');
        if (!btn) return;

        const val = instance.getValue();
        const hasValue = val !== '' && val !== null && val !== undefined;

        btn.style.display = hasValue ? '' : 'none';
    }

    bindAsyncPreload(select, instance) {
        if (select.dataset.mode !== 'async' || select.dataset.preload !== 'true') return;

        instance.load('');
        select.addEventListener('focus', () => {
            if (!instance.loading) instance.load('');
        });
    }

    bindDropdownPositioning(select, instance) {
        instance.on('dropdown_open', () => {
            const dropdown = instance.dropdown;
            if (!dropdown) return;

            const val = instance.getValue();
            if (val) {
                const option = instance.getOption(val);
                if (option) instance.setActiveOption(option);
            }

            dropdown.style.position = 'fixed';
            this.positionDropdown(instance);

            let rafId = 0;
            let lastLeft = null;
            let lastTop = null;
            let lastWidth = null;

            const reposition = () => {
                if (!instance.isOpen) return;
                if (rafId) return;
                rafId = window.requestAnimationFrame(() => {
                    rafId = 0;
                    const control = instance.control;
                    const dd = instance.dropdown;
                    if (!control || !dd) return;

                    const rect = control.getBoundingClientRect();
                    const left = rect.left;
                    const top = rect.bottom + 4;
                    const width = rect.width;

                    if (left === lastLeft && top === lastTop && width === lastWidth) return;
                    lastLeft = left;
                    lastTop = top;
                    lastWidth = width;

                    dd.style.left = left + 'px';
                    dd.style.top = top + 'px';
                    dd.style.width = width + 'px';
                    dd.style.minWidth = width + 'px';
                });
            };

            window.addEventListener('scroll', reposition, { capture: true, passive: true });
            window.addEventListener('resize', reposition, { passive: true });
            this.dropdownRepositionHandlers.set(select, reposition);
        });

        instance.on('dropdown_close', () => {
            const dropdown = instance.dropdown;
            if (dropdown) dropdown.style.position = '';

            const reposition = this.dropdownRepositionHandlers.get(select);
            if (reposition) {
                window.removeEventListener('scroll', reposition, true);
                window.removeEventListener('resize', reposition);
                this.dropdownRepositionHandlers.delete(select);
            }
        });
    }

    ensureNativeChangeListener(select) {
        if (select._nativeChangeListenerAttached) return;
        select.addEventListener('change', () => {
            if (select._dispatchingSyntheticChange) return;
            select._lastNativeChangeAt = Date.now();
        });
        select._nativeChangeListenerAttached = true;
    }

    dispatchSyntheticChange(select) {
        if (select._dispatchingSyntheticChange) return;

        const now = Date.now();
        const lastNative = select._lastNativeChangeAt || 0;
        if (now - lastNative < 150) return;

        select._dispatchingSyntheticChange = true;
        try {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        } finally {
            select._dispatchingSyntheticChange = false;
        }
    }

    getSelectElements(root) {
        const scope = root instanceof Element ? root : document;
        const selects = Array.from(scope.querySelectorAll('[data-select]'));

        if (scope instanceof Element && scope.matches('[data-select]')) {
            selects.unshift(scope);
        }

        return selects;
    }

    resolvePlaceholder(select) {
        const explicit = select.getAttribute('placeholder') || select.dataset.placeholder;
        if (explicit) return explicit;

        const container = select.closest('[data-select-placeholder]');
        if (container?.dataset?.selectPlaceholder) return container.dataset.selectPlaceholder;

        return translate('def.select_option') || 'Select...';
    }

    applyWrapperState(select, instance) {
        const wrapper = instance?.wrapper;
        if (!wrapper) return;

        const placeholder = this.resolvePlaceholder(select);
        const enableSearch = this.getEnableSearch(select);
        const allowEmpty = this.isAllowEmpty(select);

        if (!select.multiple && !enableSearch) {
            wrapper.dataset.placeholder = placeholder;
        } else {
            delete wrapper.dataset.placeholder;
        }

        if (allowEmpty) {
            wrapper.dataset.allowEmpty = 'true';
        } else {
            delete wrapper.dataset.allowEmpty;
        }
    }

    isAllowEmpty(select) {
        if (select.dataset.allowEmpty === 'true') return true;
        if (select.dataset.allowEmpty === 'false') return false;
        return !!select.querySelector('option[value=""]');
    }

    setSearchPlaceholder(instance) {
        if (!instance.dropdown) return;
        const searchInput = instance.dropdown.querySelector('.dropdown-input');
        if (searchInput && !searchInput.getAttribute('placeholder')) {
            searchInput.placeholder = translate('def.search') || 'Search...';
        }
    }

    isInstanceStale(select, instance) {
        if (!instance) return true;
        if (instance.input && instance.input !== select) return true;
        if (select.tomselect && select.tomselect !== instance) return true;

        const wrapper = instance.wrapper;
        if (!wrapper || !document.body.contains(wrapper)) return true;

        const control = instance.control;
        if (!control || !document.body.contains(control)) return true;

        if (!select.classList.contains('tomselected')) return true;

        return false;
    }

    destroyInstance(select, instance) {
        try {
            instance.destroy();
        } catch (e) {
            // ignore
        }

        const reposition = this.dropdownRepositionHandlers.get(select);
        if (reposition) {
            window.removeEventListener('scroll', reposition, true);
            window.removeEventListener('resize', reposition);
            this.dropdownRepositionHandlers.delete(select);
        }

        if (select.tomselect === instance) {
            try {
                delete select.tomselect;
            } catch (e) {
                select.tomselect = null;
            }
        }

        this.instances.delete(select);
    }

    positionDropdown(instance) {
        const control = instance.control;
        const dropdown = instance.dropdown;
        if (!control || !dropdown) return;

        const rect = control.getBoundingClientRect();
        dropdown.style.left = rect.left + 'px';
        dropdown.style.top = rect.bottom + 4 + 'px';
        dropdown.style.width = rect.width + 'px';
        dropdown.style.minWidth = rect.width + 'px';
    }

    getConfig(select) {
        const isMultiple = select.multiple;
        const enableSearch = this.getEnableSearch(select);
        const placeholder = this.resolvePlaceholder(select);
        const plugins = this.getPlugins(select, enableSearch);

        const config = {
            allowEmptyOption: !isMultiple,
            maxItems: parseInt(select.dataset.maxItems || (isMultiple ? null : 1)),
            hideSelected: select.dataset.hideSelected === 'true',
            plugins: plugins,
            render: this.getRenderFunctions(select),
            placeholder: placeholder,
            dropdownParent: 'body',
            controlInput: (!isMultiple && !enableSearch) ? null : undefined,
            onItemAdd: (value) => {
                if (isMultiple && value === '') {
                    setTimeout(() => this.instances.get(select)?.removeItem(''), 0);
                }
            },
            onItemRemove: () => {},
        };

        if (select.dataset.allowAdd === 'true') {
            config.create = true;
            config.persist = false;
        }

        if (select.dataset.mode === 'async') {
            Object.assign(config, this.getAsyncConfig(select));
        }

        return config;
    }

    getEnableSearch(select) {
        const mode = select.dataset.mode || 'static';
        const raw = (select.dataset.searchable || 'auto').toLowerCase();
        const threshold = parseInt(select.dataset.searchThreshold || '6', 10);

        if (raw === 'true') return true;
        if (raw === 'false') return false;

        if (mode !== 'static') return true;

        return select.querySelectorAll('option').length > threshold;
    }

    getPlugins(select, enableSearch) {
        const isMultiple = select.multiple;
        try {
            const plugins = JSON.parse(select.dataset.plugins || '[]');
            const normalized = [...new Set(plugins)];

            if (isMultiple) {
                if (!normalized.includes('remove_button')) {
                    normalized.push('remove_button');
                }
                const idx = normalized.indexOf('clear_button');
                if (idx !== -1) normalized.splice(idx, 1);
            } else {
                if (!normalized.includes('clear_button')) {
                    normalized.push('clear_button');
                }
                if (enableSearch && !normalized.includes('dropdown_input')) {
                    normalized.push('dropdown_input');
                }
                if (!enableSearch) {
                    const idx = normalized.indexOf('dropdown_input');
                    if (idx !== -1) normalized.splice(idx, 1);
                }
            }

            return normalized;
        } catch (e) {
            console.warn('Invalid plugins configuration:', e);
            return isMultiple ? ['remove_button'] : ['clear_button'];
        }
    }

    getRenderFunctions(select) {
        const render = {
            option: this.renderOption,
            item: this.renderItem,
            no_results: this.renderNoResults,
        };

        if (select.dataset.renderOption) {
            try {
                render.option = new Function('data', 'escape', select.dataset.renderOption);
            } catch (e) {
                console.warn('Invalid render option function:', e);
            }
        }

        if (select.dataset.renderItem) {
            try {
                render.item = new Function('data', 'escape', select.dataset.renderItem);
            } catch (e) {
                console.warn('Invalid render item function:', e);
            }
        }

        if (select.dataset.renderNoResults) {
            try {
                render.no_results = new Function('data', 'escape', select.dataset.renderNoResults);
            } catch (e) {
                console.warn('Invalid render no results function:', e);
            }
        }

        return render;
    }

    getAsyncConfig(select) {
        return {
            load: (query, callback) => {
                const minLength = parseInt(select.dataset.searchMinLength ?? 2);

                if (query.length < minLength && query !== '') {
                    return callback();
                }

                const searchData = {
                    query,
                    entity: select.dataset.entity,
                    displayField: select.dataset.displayField,
                    valueField: select.dataset.valueField,
                    searchFields: select.dataset.searchFields
                        ? JSON.parse(select.dataset.searchFields)
                        : [],
                };

                const queryParams = new URLSearchParams(searchData).toString();
                select.classList.add('is-loading');

                fetch(`${select.dataset.searchUrl}?${queryParams}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then((response) => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then((data) => {
                        callback(data);
                        select.classList.remove('is-loading');
                    })
                    .catch((error) => {
                        console.error('Error loading options:', error);
                        callback();
                        select.classList.remove('is-loading');
                    });
            },
            loadThrottle: parseInt(select.dataset.searchDelay ?? 300),
        };
    }

    renderOption(data, escape) {
        return `<div class="ts-option">
            ${data.icon ? `<i class="${escape(data.icon)}"></i>` : ''}
            <span>${escape(data.text)}</span>
        </div>`;
    }

    renderItem(data, escape) {
        return `<div class="ts-item">
            ${data.icon ? `<i class="${escape(data.icon)}"></i>` : ''}
            <span>${escape(data.text)}</span>
        </div>`;
    }

    renderNoResults() {
        return `<div class="ts-no-results">
            ${translate('def.no_results_found') ?? 'No results found'}
        </div>`;
    }

    clear() {
        this.instances.forEach((instance) => {
            try {
                instance.clear();
            } catch (e) {
                console.warn('Error clearing select instance:', e);
            }
        });
    }

    destroy() {
        this.instances.forEach((instance) => {
            try {
                instance.destroy();
            } catch (e) {
                console.warn('Error destroying select instance:', e);
            }
        });
        this.dropdownRepositionHandlers.forEach((reposition) => {
            try {
                window.removeEventListener('scroll', reposition, true);
                window.removeEventListener('resize', reposition);
            } catch (e) {
                // ignore
            }
        });
        this.instances.clear();
        this.dropdownRepositionHandlers.clear();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.Select = new Select();
});
