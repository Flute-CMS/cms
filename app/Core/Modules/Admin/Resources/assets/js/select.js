/**
 * Select component using Tom Select
 * @see https://tom-select.js.org/
 */
class Select {
    constructor() {
        this.instances = new Map();
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
                
                select.dispatchEvent(new Event('change', { bubbles: true }));
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
        });
    }

    /**
     * Get Tom Select configuration
     */
    getConfig(select) {
        const isMultiple = select.multiple;
        
        const config = {
            allowEmptyOption: !isMultiple,
            maxItems: parseInt(select.dataset.maxItems || (isMultiple ? null : 1)),
            plugins: this.getPlugins(select),
            render: this.getRenderFunctions(select),
            placeholder: select.getAttribute('placeholder') || null,
            onItemAdd: (value) => {
                if (isMultiple && value === '') {
                    setTimeout(() => this.instances.get(select)?.removeItem(''), 0);
                }
                select.dispatchEvent(new Event('change', { bubbles: true }));
            },
            onItemRemove: () => {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            },
        };

        if (isMultiple && !config.plugins.includes('remove_button')) {
            config.plugins.push('remove_button');
        }

        if (select.dataset.mode === 'async') {
            this.configureAsyncLoading(config, select);
        }

        return config;
    }

    /**
     * Get configured plugins
     */
    getPlugins(select) {
        try {
            const plugins = JSON.parse(select.dataset.plugins || '[]');
            return [...new Set(['clear_button', ...plugins])];
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
        this.instances.clear();
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    window.Select = new Select();
});

// Initialize on HTMX load
document.addEventListener('htmx:load', () => {
    window.Select?.destroy();
    window.Select = new Select();
});
