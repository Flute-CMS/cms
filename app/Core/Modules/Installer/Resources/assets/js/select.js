class InstallerSelect {
    constructor() {
        this.instances = new WeakMap();
        this.dropdownRepositionHandlers = new WeakMap();
        this.init();
    }

    init() {
        document.querySelectorAll('[data-tom-select]').forEach((select) => {
            this.initSelect(select);
        });
    }

    initSelect(select) {
        if (typeof TomSelect === 'undefined') return;
        
        if (select.tomselect) {
            return;
        }
        
        if (select.classList.contains('tomselected')) {
            select.classList.remove('tomselected');
            const wrapper = select.closest('.ts-wrapper');
            if (wrapper) {
                wrapper.replaceWith(select);
            }
        }

        const config = this.getConfig(select);
        const instance = new TomSelect(select, config);
        this.instances.set(select, instance);

        if (instance.settings.mode === 'multi' && instance.items.includes('')) {
            instance.removeItem('', true);
        }

        instance.on('dropdown_open', () => {
            const dropdown = instance.dropdown;
            if (dropdown) {
                dropdown.style.position = 'fixed';
                this.positionDropdown(instance);
            }

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
                    const dropdown = instance.dropdown;
                    if (!control || !dropdown) return;

                    const rect = control.getBoundingClientRect();
                    const left = rect.left;
                    const top = rect.bottom + 4;
                    const width = rect.width;

                    if (left === lastLeft && top === lastTop && width === lastWidth) return;
                    lastLeft = left;
                    lastTop = top;
                    lastWidth = width;

                    dropdown.style.left = left + 'px';
                    dropdown.style.top = top + 'px';
                    dropdown.style.width = width + 'px';
                    dropdown.style.minWidth = width + 'px';
                });
            };

            window.addEventListener('scroll', reposition, { capture: true, passive: true });
            window.addEventListener('resize', reposition, { passive: true });
            this.dropdownRepositionHandlers.set(select, { reposition, getRafId: () => rafId });
        });

        instance.on('dropdown_close', () => {
            const dropdown = instance.dropdown;
            if (dropdown) {
                dropdown.style.position = '';
            }
            const handlers = this.dropdownRepositionHandlers.get(select);
            if (handlers?.reposition) {
                window.removeEventListener('scroll', handlers.reposition, true);
                window.removeEventListener('resize', handlers.reposition);
            }
        });
    }

    positionDropdown(instance) {
        const control = instance.control;
        const dropdown = instance.dropdown;
        if (!control || !dropdown) return;

        const rect = control.getBoundingClientRect();
        dropdown.style.left = rect.left + 'px';
        dropdown.style.top = (rect.bottom + 4) + 'px';
        dropdown.style.width = rect.width + 'px';
        dropdown.style.minWidth = rect.width + 'px';
    }

    getConfig(select) {
        const isMultiple = select.multiple;
        const allowEmpty = select.dataset.allowEmpty === 'true';
        const allowAdd = select.dataset.allowAdd === 'true';
        const searchable = select.dataset.searchable === 'true';
        const placeholder = select.dataset.placeholder || select.getAttribute('placeholder') || '';

        const config = {
            allowEmptyOption: allowEmpty,
            maxItems: isMultiple ? null : 1,
            plugins: this.getPlugins(isMultiple, searchable),
            render: this.getRenderFunctions(),
            placeholder: placeholder,
            onItemAdd: (value) => {
                if (isMultiple && value === '') {
                    const inst = this.instances.get(select);
                    if (inst) setTimeout(() => inst.removeItem('', true), 0);
                }
            },
            dropdownParent: 'body',
            controlInput: (!isMultiple && !searchable) ? null : undefined,
        };

        if (allowAdd) {
            config.create = true;
            config.persist = false;
        }

        if (isMultiple) {
            config.plugins.push('remove_button');
        }

        return config;
    }

    getPlugins(isMultiple, searchable) {
        const plugins = [];
        if (!isMultiple && searchable) {
            plugins.push('dropdown_input');
        }
        return plugins;
    }

    getRenderFunctions() {
        return {
            option: (data, escape) => `<div class="ts-opt">${escape(data.text)}</div>`,
            item: (data, escape) => `<div class="ts-itm">${escape(data.text)}</div>`,
            no_results: () => `<div class="ts-empty">No results</div>`,
        };
    }

    destroySelect(select) {
        const instance = this.instances.get(select);
        if (instance) {
            try {
                instance.destroy();
            } catch (e) { }
            this.instances.delete(select);
        }
        const handlers = this.dropdownRepositionHandlers.get(select);
        if (handlers?.reposition) {
            window.removeEventListener('scroll', handlers.reposition, true);
            window.removeEventListener('resize', handlers.reposition);
        }
        this.dropdownRepositionHandlers.delete(select);
    }

    refresh() {
        this.init();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.InstallerSelect = new InstallerSelect();
});

document.addEventListener('htmx:beforeSwap', (evt) => {
    if (!window.InstallerSelect) return;
    const target = evt.detail.target;
    if (!target) return;
    
    target.querySelectorAll('[data-tom-select]').forEach((select) => {
        window.InstallerSelect.destroySelect(select);
    });
});

htmx.onLoad(() => {
    if (window.InstallerSelect) {
        window.InstallerSelect.init();
    }
});

