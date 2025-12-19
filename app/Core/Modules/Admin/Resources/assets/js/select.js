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

    /**
     * Initialize select fields
     */
    init() {
        document.querySelectorAll('[data-select]').forEach((select) => {
            if (this.instances.has(select)) return;

            const config = this.getConfig(select);
            const instance = new TomSelect(select, config);
            this.instances.set(select, instance);

            if (instance.wrapper) {
                instance.wrapper.style.width = '100%';
            }

            instance.sync();

            const initVal = select.dataset.initialValue;
            if (initVal !== undefined && initVal !== 'null' && initVal !== '') {
                try {
                    let parsed = JSON.parse(initVal);
                    if (parsed !== null) {
                        instance.setValue(parsed, true);
                    }
                } catch (e) {
                    if (initVal) instance.setValue(initVal, true);
                }
            }

            // Hack: make native select "visible" for Yoyo engine to pick up the value
            // (Yoyo might ignore display:none elements when collecting data)
            select.style.display = 'block';
            select.style.position = 'absolute';
            select.style.top = '0';
            select.style.left = '0';
            select.style.opacity = '0';
            select.style.pointerEvents = 'none';
            select.style.width = '1px';
            select.style.height = '1px';
            select.style.zIndex = '-1';

            instance.on('change', () => {
                if (instance.settings.mode === 'multi' && instance.items.includes('')) {
                    instance.removeItem('');
                }

                if (select.dataset.yoyo) {
                    const yoyoValue = select.dataset.yoyoValue;
                    if (yoyoValue) {
                        instance.setValue(yoyoValue);
                    }
                }

                if (select._changeTimeout) clearTimeout(select._changeTimeout);
                select._changeTimeout = setTimeout(() => {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }, 100);
            });

            if (instance.settings.mode === 'multi' && instance.items.includes('')) {
                instance.removeItem('');
            }

            if (
                select.dataset.mode === 'async' &&
                select.dataset.preload === 'true'
            ) {
                instance.load('');
            }

            select.addEventListener('focus', () => {
                if (
                    select.dataset.mode === 'async' &&
                    select.dataset.preload === 'true' &&
                    !instance.loading
                ) {
                    instance.load('');
                }
            });

            // Make dropdown escape overflow containers (native-like)
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
        });
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

    /**
     * Get Tom Select configuration
     */
    getConfig(select) {
        const isMultiple = select.multiple;
        const enableSearch = this.getEnableSearch(select);

        const config = {
            allowEmptyOption: !isMultiple,
            maxItems: parseInt(select.dataset.maxItems || (isMultiple ? null : 1)),
            plugins: this.getPlugins(select, enableSearch),
            render: this.getRenderFunctions(select),
            placeholder: select.getAttribute('placeholder') || null,
            dropdownParent: 'body',
            // Hide the control input for single selects when search is disabled
            controlInput: (!isMultiple && !enableSearch) ? null : undefined,
            onItemAdd: (value) => {
                if (isMultiple && value === '') {
                    setTimeout(() => this.instances.get(select)?.removeItem(''), 0);
                }
            },
            onItemRemove: () => {
            },
        };

        if (select.dataset.allowAdd === 'true') {
            config.create = true;
            config.persist = false;
        }

        if (isMultiple && !config.plugins.includes('remove_button')) {
            config.plugins.push('remove_button');
        }

        if (select.dataset.mode === 'async') {
            this.configureAsyncLoading(config, select);
        }

        return config;
    }

    getEnableSearch(select) {
        const mode = select.dataset.mode || 'static';
        const raw = (select.dataset.searchable || 'auto').toLowerCase();
        const threshold = parseInt(select.dataset.searchThreshold || '6', 10);

        if (raw === 'true') return true;
        if (raw === 'false') return false;

        // Auto mode:
        // - for async/database modes: enable search
        if (mode !== 'static') return true;

        // - for static: enable when options count > threshold
        const optionCount = select.querySelectorAll('option').length;
        return optionCount > threshold;
    }

    /**
     * Get configured plugins
     */
    getPlugins(select, enableSearch) {
        const isMultiple = select.multiple;
        try {
            const plugins = JSON.parse(select.dataset.plugins || '[]');
            const normalized = [...new Set(['clear_button', ...plugins])];

            // Enable/disable dropdown search input for single select only
            const hasDropdownInput = normalized.includes('dropdown_input');
            if (!isMultiple && enableSearch && !hasDropdownInput) {
                normalized.push('dropdown_input');
            }
            if (!isMultiple && !enableSearch && hasDropdownInput) {
                return normalized.filter((p) => p !== 'dropdown_input');
            }

            return normalized;
        } catch (e) {
            console.warn('Invalid plugins configuration:', e);
            return ['clear_button'];
        }
    }

    /**
     * Get render functions
     */
    getRenderFunctions(select) {
        const render = {
            option: this.renderOption,
            item: this.renderItem,
            no_results: this.renderNoResults,
        };

        if (select.dataset.renderOption) {
            try {
                render.option = new Function(
                    'data',
                    'escape',
                    select.dataset.renderOption,
                );
            } catch (e) {
                console.warn('Invalid render option function:', e);
            }
        }

        if (select.dataset.renderItem) {
            try {
                render.item = new Function(
                    'data',
                    'escape',
                    select.dataset.renderItem,
                );
            } catch (e) {
                console.warn('Invalid render item function:', e);
            }
        }

        if (select.dataset.renderNoResults) {
            try {
                render.no_results = new Function(
                    'data',
                    'escape',
                    select.dataset.renderNoResults,
                );
            } catch (e) {
                console.warn('Invalid render no results function:', e);
            }
        }

        return render;
    }

    /**
     * Configure async loading
     */
    configureAsyncLoading(config, select) {
        config.load = (query, callback) => {
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
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
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
        };

        config.loadThrottle = parseInt(select.dataset.searchDelay ?? 300);
    }

    /**
     * Default render option template
     */
    renderOption(data, escape) {
        return `<div class="ts-option">
            ${data.icon ? `<i class="${escape(data.icon)}"></i>` : ''}
            <span>${escape(data.text)}</span>
        </div>`;
    }

    /**
     * Default render selected item template
     */
    renderItem(data, escape) {
        return `<div class="ts-item">
            ${data.icon ? `<i class="${escape(data.icon)}"></i>` : ''}
            <span>${escape(data.text)}</span>
        </div>`;
    }

    /**
     * Default render no results template
     */
    renderNoResults() {
        return `<div class="ts-no-results">
            ${translate('def.no_results_found') ?? 'No results found'}
        </div>`;
    }

    /**
     * Clear all select instances (for reinitialization)
     */
    clear() {
        this.instances.forEach((instance, select) => {
            try {
                instance.clear();
            } catch (e) {
                console.warn('Error clearing select instance:', e);
            }
        });
    }

    /**
     * Destroy all select instances
     */
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

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    window.Select = new Select();
});

document.addEventListener('htmx:load', () => {
    window.Select?.destroy();
    window.Select = new Select();
});

document.body.addEventListener('htmx:afterSwap', (evt) => {
    setTimeout(() => {
        window.Select?.destroy();
        window.Select = new Select();
    }, 10);
});

document.body.addEventListener('htmx:afterSettle', (evt) => {
    if (window.Select) {
        window.Select.init();
    }
});
