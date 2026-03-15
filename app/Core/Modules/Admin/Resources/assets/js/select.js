/**
 * Select component using FluteSelect
 * @see https://github.com/FlamesONE/flute-select
 */
class Select {
    constructor() {
        this.instances = new Map();
        this._swapping = false;
        this.init();
    }

    cleanup() {
        this.instances.forEach((instance, select) => {
            if (!document.body.contains(select)) {
                this.destroyInstance(select);
            }
        });
    }

    init(root = document) {
        this.cleanup();

        this.getSelectElements(root).forEach((select) => {
            const existingInstance = this.instances.get(select);
            if (existingInstance) {
                return;
            }

            const fsInstance = FluteSelect.get(select);
            if (fsInstance) {
                this.instances.set(select, fsInstance);
                return;
            }

            this.createInstance(select);
        });
    }

    createInstance(select) {
        const config = this.getConfig(select);
        const instance = FluteSelect.create(select, config);
        this.instances.set(select, instance);

        this.applyInitialValue(select, instance);
        this.bindChangeHandler(select, instance);
        this.bindAsyncPreload(select, instance);

        return instance;
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

    bindChangeHandler(select, instance) {
        const isYoyo = this.isYoyoSelect(select);

        instance.on('change', ({ value }) => {
            if (select._changeTimeout) clearTimeout(select._changeTimeout);

            select._changeTimeout = setTimeout(() => {
                if (isYoyo) {
                    // For Yoyo selects: dispatch a non-internal change so HTMX picks it up.
                    // The native select is already synced by FluteSelect's FormBridge,
                    // but that event has _fsSyncInternal=true which we filter out.
                    // We need a clean change event for HTMX to trigger Yoyo re-render.
                    this.dispatchYoyoChange(select);
                } else {
                    this.dispatchSyntheticChange(select);
                }
            }, 100);
        });

        if (isYoyo) {
            // Filter out internal sync events from FluteSelect FormBridge
            // to prevent infinite loop: FluteSelect change → FormBridge syncs native select
            // → native select fires change → bubbles to wrapper → HTMX picks it up → Yoyo re-renders → repeat
            select.addEventListener('change', (e) => {
                if (e._fsSyncInternal) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
            });
        }
    }

    isYoyoSelect(select) {
        const wrapper = select.closest('.select-wrapper');
        return !!(wrapper && wrapper.hasAttribute('yoyo'));
    }

    dispatchYoyoChange(select) {
        if (this._swapping) return;

        const wrapper = select.closest('.select-wrapper');
        if (!wrapper) return;

        // Dispatch change event on the wrapper (where hx-trigger is set)
        wrapper.dispatchEvent(new Event('change', { bubbles: true }));
    }

    bindAsyncPreload(select, instance) {
        if (select.dataset.mode !== 'async' || select.dataset.preload !== 'true') return;
        if (instance._loader) {
            instance._loader.load(1, '');
        }
    }

    dispatchSyntheticChange(select) {
        if (select._dispatchingSyntheticChange || this._swapping) return;

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

        const emptyOpt = select.querySelector('option[value=""]');
        if (emptyOpt) {
            const text = emptyOpt.textContent.trim();
            if (text) return text;
        }

        const container = select.closest('[data-select-placeholder]');
        if (container?.dataset?.selectPlaceholder) return container.dataset.selectPlaceholder;

        return translate('def.select_option') || 'Select...';
    }

    getConfig(select) {
        const isMultiple = select.multiple;
        const enableSearch = this.getEnableSearch(select);
        const placeholder = this.resolvePlaceholder(select);
        const isAllowEmpty = this.isAllowEmpty(select);

        const positioning = select.dataset.positioning || 'dropdown';

        const config = {
            placeholder: placeholder,
            multiple: isMultiple,
            searchable: enableSearch,
            clearable: isAllowEmpty,
            positioning: positioning,
            maxItems: parseInt(select.dataset.maxItems || (isMultiple ? 0 : 0)),
            name: select.getAttribute('name') || '',
            disabled: select.disabled,
        };

        const richOptions = this.collectRichOptions(select);
        if (richOptions.length > 0) {
            config.options = richOptions;
            const selectedValues = Array.from(select.selectedOptions || []).map(o => o.value).filter(Boolean);
            if (selectedValues.length > 0) {
                config.value = isMultiple ? selectedValues : selectedValues[0];
            }
        }

        if (select.dataset.allowAdd === 'true') {
            config.creatable = true;
        }

        if (select.dataset.mode === 'async') {
            Object.assign(config, this.getAsyncConfig(select));
        }

        if (select.dataset.renderOption) {
            const fn = this.resolveRenderFunction(select.dataset.renderOption);
            if (fn) config.renderOption = fn;
        }

        if (select.dataset.renderItem) {
            const fn = this.resolveRenderFunction(select.dataset.renderItem);
            if (fn) config.renderSelected = fn;
        }

        if (select.dataset.renderNoResults) {
            const fn = this.resolveRenderFunction(select.dataset.renderNoResults);
            if (fn) config.renderEmpty = fn;
        }

        return config;
    }

    collectRichOptions(select) {
        const result = [];
        for (const opt of select.querySelectorAll('option')) {
            if (opt.value === '') continue;

            const dataAttr = opt.getAttribute('data-data');
            if (dataAttr) {
                try {
                    const data = JSON.parse(dataAttr);
                    const option = {
                        value: opt.value,
                        label: data.text || opt.textContent.trim(),
                    };
                    if (data.optionHtml) {
                        option.html = data.optionHtml;
                    }
                    if (data.icon) {
                        option.icon = data.icon;
                    }
                    if (data.image || data.avatar) {
                        option.image = data.image || data.avatar;
                    }
                    if (data.description || data.email) {
                        option.description = data.description || data.email;
                    }
                    option.data = data;
                    result.push(option);
                } catch (e) {
                    result.push({
                        value: opt.value,
                        label: opt.textContent.trim(),
                    });
                }
            } else {
                result.push({
                    value: opt.value,
                    label: opt.textContent.trim(),
                    disabled: opt.disabled,
                });
            }
        }
        return result;
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

    resolveRenderFunction(nameOrBody) {
        if (typeof window[nameOrBody] === 'function') {
            return window[nameOrBody];
        }

        const parts = nameOrBody.split('.');
        let ref = window;
        for (const part of parts) {
            ref = ref?.[part];
        }
        if (typeof ref === 'function') {
            return ref;
        }

        return null;
    }

    getAsyncConfig(select) {
        const searchUrl = select.dataset.searchUrl || '/admin/select/search';
        const minLength = parseInt(select.dataset.searchMinLength ?? 2);

        const params = new URLSearchParams();
        if (select.dataset.entity) params.set('entity', select.dataset.entity);
        if (select.dataset.displayField) params.set('displayField', select.dataset.displayField);
        if (select.dataset.valueField) params.set('valueField', select.dataset.valueField);
        if (select.dataset.searchFields) params.set('searchFields', select.dataset.searchFields);
        if (select.dataset.extraFields) params.set('extraFields', select.dataset.extraFields);
        if (select.dataset.optionView) params.set('optionView', select.dataset.optionView);
        if (select.dataset.itemView) params.set('itemView', select.dataset.itemView);

        const separator = searchUrl.includes('?') ? '&' : '?';
        const fullSourceUrl = searchUrl + separator + params.toString();

        return {
            source: fullSourceUrl,
            searchable: true,
            lazy: {
                pageSize: 20,
                searchParam: 'query',
                pageParam: 'page',
                debounce: parseInt(select.dataset.searchDelay ?? 300),
                loadOnInit: select.dataset.preload === 'true',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                transformResponse: (json) => {
                    const data = Array.isArray(json) ? json : (json.data || []);
                    return data.map(item => {
                        const opt = {
                            value: String(item.value),
                            label: item.text || item.label || '',
                        };
                        if (item.optionHtml) {
                            opt.html = item.optionHtml;
                        }
                        if (item.icon) {
                            opt.icon = item.icon;
                        }
                        if (item.image || item.avatar) {
                            opt.image = item.image || item.avatar;
                        }
                        if (item.description || item.email) {
                            opt.description = item.description || item.email;
                        }
                        opt.data = item;
                        return opt;
                    });
                },
            },
            renderOption: (opt, state) => {
                if (opt.data?.optionHtml) {
                    return opt.data.optionHtml;
                }
                return null;
            },
            renderSelected: (opt) => {
                if (opt.data?.itemHtml) {
                    return opt.data.itemHtml;
                }
                return null;
            },
        };

    }

    isAllowEmpty(select) {
        if (select.dataset.allowEmpty === 'true') return true;
        if (select.dataset.allowEmpty === 'false') return false;
        return !!select.querySelector('option[value=""]');
    }

    destroyInstance(select) {
        const instance = this.instances.get(select) || FluteSelect.get(select);
        if (instance) {
            try {
                instance.destroy();
            } catch (e) {
                // ignore
            }
        }
        this.instances.delete(select);
    }

    clear() {
        this.instances.forEach((instance) => {
            try {
                instance.clear();
            } catch (e) {
                // ignore
            }
        });
    }

    destroy() {
        this.instances.forEach((instance) => {
            try {
                instance.destroy();
            } catch (e) {
                // ignore
            }
        });
        this.instances.clear();
    }

    getInstance(select) {
        return this.instances.get(select) || FluteSelect.get(select);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.Select = new Select();

    document.body.addEventListener('htmx:beforeSwap', (e) => {
        if (!window.Select) return;
        const target = e.detail.target;
        if (!target) return;

        // Flag to prevent synthetic change events during swap
        window.Select._swapping = true;

        target.querySelectorAll('[data-select]').forEach((select) => {
            window.Select.destroyInstance(select);
        });

        if (target.matches?.('[data-select]')) {
            window.Select.destroyInstance(target);
        }
    });

    document.body.addEventListener('htmx:afterSettle', (e) => {
        if (!window.Select) return;

        const target = e.detail.target ?? e.detail.elt;
        if (target) {
            window.Select.init(target);
        }

        // Reset swapping flag after init
        window.Select._swapping = false;
    });
});
