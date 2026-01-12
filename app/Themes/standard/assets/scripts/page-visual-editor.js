/**
 * Visual Editor - Theme Customization
 */

const GOOGLE_FONTS = [
    'Manrope', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Nunito', 'Raleway', 'Ubuntu', 'Rubik', 'Work Sans', 'DM Sans', 'Outfit',
    'Plus Jakarta Sans', 'Space Grotesk', 'Lexend', 'Sora', 'Urbanist', 'Figtree',
    'Playfair Display', 'Merriweather', 'Lora'
];

class VisualEditor {
    constructor() {
        this.root = document.documentElement;
        this.editor = document.getElementById('visual-editor');
        this.backdrop = document.getElementById('visual-editor-backdrop');
        
        if (!this.editor) return;

        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 30;
        this.isOpen = false;
        this.loadedFonts = new Set(['Manrope']);
        this.initialState = null;
        
        this.init();
    }

    init() {
        this.cacheElements();
        this.bindEvents();
    }

    cacheElements() {
        this.closeBtn = document.getElementById('visual-editor-close');
        this.undoBtn = document.getElementById('ve-undo');
        this.redoBtn = document.getElementById('ve-redo');
        this.resetBtn = document.getElementById('ve-reset');
        this.cancelBtn = document.getElementById('ve-cancel');
        this.saveBtn = document.getElementById('ve-save');
        
        this.colorLabels = this.editor.querySelectorAll('.ve__color[data-variable]');
        this.bgTypeButtons = this.editor.querySelectorAll('.ve__bg-type');
        this.gradientColors = document.getElementById('ve-gradient-colors');
        this.sliders = this.editor.querySelectorAll('.ve__range');
        this.selects = this.editor.querySelectorAll('.ve__select');
        this.borderPreview = document.getElementById('ve-border-preview');
        
        this.segments = this.editor.querySelectorAll('.ve__segment');
        this.panels = this.editor.querySelectorAll('.ve__panel');
        
        // Toggles - the input inside toggle-switch component
        this.fullwidthToggle = document.getElementById('ve-fullwidth');
        this.shadowsToggle = document.getElementById('ve-shadows');
    }

    bindEvents() {
        // Open trigger
        document.getElementById('page-open-editor')?.addEventListener('click', () => this.open());
        
        // Close
        this.closeBtn?.addEventListener('click', () => this.close());
        this.backdrop?.addEventListener('click', () => this.close());
        this.cancelBtn?.addEventListener('click', () => this.cancel());
        this.saveBtn?.addEventListener('click', () => this.save());
        
        // History
        this.undoBtn?.addEventListener('click', () => this.undo());
        this.redoBtn?.addEventListener('click', () => this.redo());
        this.resetBtn?.addEventListener('click', () => this.reset());
        
        // Segments (tabs)
        this.segments.forEach(seg => {
            seg.addEventListener('click', () => {
                const tab = seg.dataset.tab;
                this.segments.forEach(s => s.classList.toggle('active', s === seg));
                this.panels.forEach(p => p.classList.toggle('active', p.dataset.panel === tab));
            });
        });
        
        // Colors
        this.colorLabels.forEach(label => {
            const input = label.querySelector('.ve__color-input');
            const preview = label.querySelector('.ve__color-preview');
            const variable = label.dataset.variable;
            
            input?.addEventListener('input', (e) => {
                this.updateColor(variable, e.target.value, preview);
            });
            input?.addEventListener('change', () => this.recordHistory());
        });
        
        // Background types
        this.bgTypeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.setBackgroundType(btn.dataset.bgType);
                this.recordHistory();
            });
        });
        
        // Gradient colors
        [1, 2, 3].forEach(i => {
            const input = document.getElementById(`ve-grad-${i}`);
            input?.addEventListener('input', (e) => {
                this.setProperty(`--bg-grad${i}`, e.target.value);
                this.updateBackgroundPreview();
            });
            input?.addEventListener('change', () => this.recordHistory());
        });
        
        // Sliders
        this.sliders.forEach(slider => {
            slider.addEventListener('input', (e) => this.handleSliderInput(e.target));
            slider.addEventListener('change', () => this.recordHistory());
        });
        
        // Selects
        this.selects.forEach(select => {
            select.addEventListener('change', (e) => this.handleSelectChange(e.target));
        });
        
        // Fullwidth toggle
        this.fullwidthToggle?.addEventListener('change', (e) => {
            this.handleFullwidthToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Shadows toggle
        this.shadowsToggle?.addEventListener('change', (e) => {
            this.handleShadowsToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Keyboard
        document.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;
            if (e.key === 'Escape') { e.preventDefault(); this.close(); }
            if (e.ctrlKey && e.key === 'z') { e.preventDefault(); this.undo(); }
            if (e.ctrlKey && e.key === 'y') { e.preventDefault(); this.redo(); }
        });
        
        // Theme change observer
        new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                if (m.attributeName === 'data-theme' && this.isOpen) {
                    this.loadCurrentValues();
                    this.recordHistory();
                }
            });
        }).observe(this.root, { attributes: true });
    }

    // Open/Close
    open() {
        this.isOpen = true;
        this.editor.classList.add('open');
        document.body.classList.add('ve-open');
        
        // Hide FAB
        const fab = document.getElementById('page-edit-fab');
        fab?.classList.remove('open');
        fab?.classList.add('hide');
        
        this.loadCurrentValues();
        this.initialState = this.captureState();
        this.history = [];
        this.historyIndex = -1;
        this.recordHistory();
    }

    close() {
        this.isOpen = false;
        this.editor.classList.remove('open');
        document.body.classList.remove('ve-open');
        document.getElementById('page-edit-fab')?.classList.remove('hide');
    }

    cancel() {
        if (this.initialState) {
            this.applyState(this.initialState);
        }
        this.close();
    }

    // Load current values
    loadCurrentValues() {
        const computed = getComputedStyle(this.root);
        
        // Colors
        this.colorLabels.forEach(label => {
            const variable = label.dataset.variable;
            const input = label.querySelector('.ve__color-input');
            const preview = label.querySelector('.ve__color-preview');
            const value = computed.getPropertyValue(variable).trim();
            
            if (input && preview && value) {
                try {
                    const hex = tinycolor(value).toHexString();
                    input.value = hex;
                    preview.style.background = value;
                } catch (e) {}
            }
        });
        
        // Background type
        const bgType = this.root.style.getPropertyValue('--background-type').trim() || 
                       this.root.getAttribute('data-bg-type') || 'solid';
        this.setBackgroundType(bgType, false);
        
        // Gradient colors
        [1, 2, 3].forEach(i => {
            const input = document.getElementById(`ve-grad-${i}`);
            if (input) {
                const fallback = i === 1 ? '--accent' : i === 2 ? '--primary' : '--background';
                const value = computed.getPropertyValue(`--bg-grad${i}`).trim() || 
                              computed.getPropertyValue(fallback).trim();
                try {
                    input.value = tinycolor(value).toHexString();
                } catch (e) {}
            }
        });
        
        // Sliders
        this.sliders.forEach(slider => {
            const variable = slider.dataset.variable;
            const unit = slider.dataset.unit || '';
            let value = computed.getPropertyValue(variable).trim();
            
            if (value) {
                value = parseFloat(value);
                if (!isNaN(value)) {
                    slider.value = value;
                    this.updateSliderDisplay(slider, value, unit);
                }
            }
        });
        
        // Selects (fonts)
        this.selects.forEach(select => {
            const variable = select.dataset.variable;
            const value = computed.getPropertyValue(variable).trim();
            if (value) {
                // Extract first font name from value like "'Inter', sans-serif"
                const fontName = value.replace(/['"]/g, '').split(',')[0].trim();
                const option = Array.from(select.options).find(o => o.value === fontName);
                if (option) {
                    select.value = fontName;
                }
            }
        });
        
        // Fullwidth toggle
        if (this.fullwidthToggle) {
            const mode = this.root.getAttribute('data-container-width') || 'container';
            this.fullwidthToggle.checked = mode === 'fullwidth';
        }
        
        // Shadows toggle
        if (this.shadowsToggle) {
            const shadowSmall = computed.getPropertyValue('--shadow-small').trim();
            this.shadowsToggle.checked = shadowSmall !== 'none';
        }
        
        // Border preview
        this.updateBorderPreview();
    }

    // Helpers
    getProperty(variable) {
        return getComputedStyle(this.root).getPropertyValue(variable).trim();
    }

    setProperty(variable, value) {
        this.root.style.setProperty(variable, value);
    }

    // Color handling
    updateColor(variable, color, preview) {
        const hex = tinycolor(color).toHexString();
        this.setProperty(variable, hex);
        if (preview) preview.style.background = hex;
        
        this.generateShades(variable, hex);
        
        if (['--accent', '--primary', '--background'].includes(variable)) {
            this.updateBackgroundPreview();
        }
    }

    generateShades(variable, baseColor) {
        const isLight = this.root.getAttribute('data-theme') === 'light';
        const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
        const hsl = tinycolor(baseColor).toHsl();
        
        const darkTargets = { 50: 96, 100: 90, 200: 80, 300: 70, 400: 60, 500: 50, 600: 40, 700: 30, 800: 20, 900: 12, 950: 8 };
        
        steps.forEach(step => {
            let shade;
            if (step === 500) {
                shade = tinycolor(baseColor).toHexString();
            } else if (isLight) {
                if (step < 500) {
                    shade = tinycolor.mix(baseColor, '#000000', ((500 - step) / 450) * 100).toHexString();
                } else {
                    shade = tinycolor.mix(baseColor, '#FFFFFF', ((step - 500) / 450) * 100).toHexString();
                }
            } else {
                shade = tinycolor({ h: hsl.h, s: hsl.s, l: darkTargets[step] / 100 }).toHexString();
            }
            this.setProperty(`${variable}-${step}`, shade);
        });
    }

    // Background
    setBackgroundType(type, updatePreview = true) {
        this.setProperty('--background-type', type);
        this.root.setAttribute('data-bg-type', type);
        
        this.bgTypeButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.bgType === type));
        
        if (this.gradientColors) {
            this.gradientColors.hidden = type === 'solid';
        }
        
        if (updatePreview) this.updateBackgroundPreview();
    }

    updateBackgroundPreview() {
        const type = this.root.style.getPropertyValue('--background-type').trim() || 'solid';
        const computed = getComputedStyle(this.root);
        const bg = computed.getPropertyValue('--background').trim();
        const g1 = computed.getPropertyValue('--bg-grad1').trim() || computed.getPropertyValue('--accent').trim();
        const g2 = computed.getPropertyValue('--bg-grad2').trim() || computed.getPropertyValue('--primary').trim();
        const g3 = computed.getPropertyValue('--bg-grad3').trim() || bg;
        
        let style = bg;
        
        switch (type) {
            case 'linear-gradient':
                style = `linear-gradient(145deg, ${bg} 0%, ${bg} 60%, ${this.mix(g1, bg, 0.12)} 100%)`;
                break;
            case 'radial-gradient':
                style = `radial-gradient(ellipse 120% 100% at 80% 0%, ${this.mix(g1, bg, 0.15)} 0%, ${bg} 50%)`;
                break;
            case 'mesh-gradient':
                style = `radial-gradient(ellipse at 15% 15%, ${this.mix(g1, bg, 0.12)} 0%, transparent 50%),
                         radial-gradient(ellipse at 85% 80%, ${this.mix(g2, bg, 0.1)} 0%, transparent 50%), ${bg}`;
                break;
            case 'subtle-gradient':
                style = `linear-gradient(160deg, ${bg} 0%, ${this.mix(g1, bg, 0.06)} 100%)`;
                break;
            case 'aurora-gradient':
                style = `radial-gradient(ellipse 150% 80% at 10% 20%, ${this.mix(g1, bg, 0.12)} 0%, transparent 50%),
                         radial-gradient(ellipse 100% 60% at 90% 30%, ${this.mix(g2, bg, 0.1)} 0%, transparent 50%),
                         radial-gradient(ellipse 120% 80% at 50% 90%, ${this.mix(g3, bg, 0.08)} 0%, transparent 60%), ${bg}`;
                break;
        }
        
        document.body.style.background = style;
    }

    mix(c1, c2, amount) {
        return tinycolor.mix(c2, c1, amount * 100).toHexString();
    }

    // Slider handling
    handleSliderInput(slider) {
        const variable = slider.dataset.variable;
        const unit = slider.dataset.unit || '';
        const value = parseFloat(slider.value);
        
        this.setProperty(variable, value + unit);
        this.updateSliderDisplay(slider, value, unit);
        
        // Special handling for border radius
        if (variable === '--border1') {
            this.setProperty('--border05', (value / 2) + 'rem');
            this.updateBorderPreview();
        }
        
        // Max content width - apply to containers immediately
        if (variable === '--max-content-width') {
            if (!this.fullwidthToggle?.checked) {
                document.querySelectorAll('.container:not(.keep-container)').forEach(c => {
                    c.style.maxWidth = value + unit;
                });
            }
        }
    }

    updateSliderDisplay(slider, value, unit) {
        const parent = slider.closest('.ve__field') || slider.closest('.ve__row') || slider.closest('.ve__slider-wrap');
        const valueEl = parent?.querySelector('.ve__range-val');
        if (valueEl) {
            valueEl.textContent = value + unit;
        }
    }

    updateBorderPreview() {
        if (this.borderPreview) {
            const radius = this.getProperty('--border1') || '1rem';
            this.borderPreview.style.borderRadius = radius;
        }
    }

    // Select handling
    handleSelectChange(select) {
        const variable = select.dataset.variable;
        const value = select.value;
        
        if (value === 'inherit') {
            this.setProperty(variable, 'var(--font)');
        } else {
            this.loadFont(value);
            this.setProperty(variable, `'${value}', sans-serif`);
        }
        this.recordHistory();
    }

    // Fullwidth toggle
    handleFullwidthToggle(checked) {
        const mode = checked ? 'fullwidth' : 'container';
        this.setProperty('--container-width', mode);
        this.root.setAttribute('data-container-width', mode);
        
        const containers = document.querySelectorAll('.container:not(.keep-container)');
        containers.forEach(c => {
            if (checked) {
                c.style.maxWidth = 'none';
                c.style.width = '100%';
            } else {
                // Restore max-content-width
                const maxWidth = this.getProperty('--max-content-width') || '1200px';
                c.style.maxWidth = maxWidth;
                c.style.width = '';
            }
        });
        
        localStorage.setItem('container-width-mode', mode);
    }

    // Shadows toggle
    handleShadowsToggle(enabled) {
        if (enabled) {
            this.setProperty('--shadow-small', 'inset 0 1px 2px #ffffff30, 0 1px 2px #00000030, 0 2px 4px #00000015');
            this.setProperty('--shadow-medium', 'inset 0 1px 2px #ffffff50, 0 1px 2px #00000030, 0 2px 4px #00000015');
            this.setProperty('--shadow-large', 'inset 0 1px 2px #ffffff70, 0 1px 2px #00000030, 0 2px 4px #00000015');
        } else {
            this.setProperty('--shadow-small', 'none');
            this.setProperty('--shadow-medium', 'none');
            this.setProperty('--shadow-large', 'none');
        }
    }

    // Font loading
    loadFont(fontName) {
        if (this.loadedFonts.has(fontName)) return;
        
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fontName)}:wght@400;500;600;700&display=swap`;
        document.head.appendChild(link);
        this.loadedFonts.add(fontName);
    }

    // History - capture from inline styles only (what we've changed)
    captureState() {
        const state = {};
        const inlineVars = [
            '--accent', '--primary', '--secondary', '--background', '--text',
            '--font', '--font-header', '--font-scale',
            '--border1', '--border05',
            '--space-xs', '--space-sm', '--space-md', '--space-lg', '--space-xl',
            '--transition', '--blur-amount', '--max-content-width',
            '--shadow-small', '--shadow-medium', '--shadow-large',
            '--background-type', '--bg-grad1', '--bg-grad2', '--bg-grad3', '--container-width'
        ];
        
        inlineVars.forEach(key => {
            const val = this.root.style.getPropertyValue(key);
            if (val) state[key] = val;
        });
        
        // Shades
        ['--accent', '--primary', '--secondary', '--background', '--text'].forEach(variable => {
            [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach(step => {
                const key = `${variable}-${step}`;
                const val = this.root.style.getPropertyValue(key);
                if (val) state[key] = val;
            });
        });
        
        state['_container-width'] = this.root.getAttribute('data-container-width') || 'container';
        
        return state;
    }

    applyState(state) {
        // Clear all inline styles first
        const allVars = [
            '--accent', '--primary', '--secondary', '--background', '--text',
            '--font', '--font-header', '--font-scale',
            '--border1', '--border05',
            '--space-xs', '--space-sm', '--space-md', '--space-lg', '--space-xl',
            '--transition', '--blur-amount', '--max-content-width',
            '--shadow-small', '--shadow-medium', '--shadow-large',
            '--background-type', '--bg-grad1', '--bg-grad2', '--bg-grad3', '--container-width'
        ];
        
        allVars.forEach(key => this.root.style.removeProperty(key));
        
        // Apply state
        Object.entries(state).forEach(([key, value]) => {
            if (key.startsWith('--') && value) {
                this.setProperty(key, value);
            } else if (key === '_container-width') {
                this.root.setAttribute('data-container-width', value);
                if (this.fullwidthToggle) {
                    this.fullwidthToggle.checked = value === 'fullwidth';
                }
                this.handleFullwidthToggle(value === 'fullwidth');
            }
        });
        
        this.loadCurrentValues();
        this.updateBackgroundPreview();
        this.updateBorderPreview();
    }

    recordHistory() {
        const state = this.captureState();
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(state);
        if (this.history.length > this.maxHistory) this.history.shift();
        else this.historyIndex++;
        this.updateHistoryButtons();
    }

    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.applyState(this.history[this.historyIndex]);
            this.updateHistoryButtons();
        }
    }

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.applyState(this.history[this.historyIndex]);
            this.updateHistoryButtons();
        }
    }

    updateHistoryButtons() {
        if (this.undoBtn) this.undoBtn.disabled = this.historyIndex <= 0;
        if (this.redoBtn) this.redoBtn.disabled = this.historyIndex >= this.history.length - 1;
    }

    reset() {
        this.root.removeAttribute('style');
        document.body.style.background = '';
        this.root.removeAttribute('data-bg-type');
        this.root.setAttribute('data-container-width', 'container');
        
        document.querySelectorAll('.container:not(.keep-container)').forEach(c => {
            c.style.maxWidth = '';
            c.style.width = '';
        });
        
        localStorage.removeItem('container-width-mode');
        
        this.loadCurrentValues();
        this.recordHistory();
        
        if (typeof notyf !== 'undefined') {
            notyf.success(translate('page-edit.settings_reset') || 'Settings reset');
        }
    }

    // Save - collect all changed settings
    async save() {
        const theme = this.root.getAttribute('data-theme') || 'dark';
        const state = this.captureState();
        
        const colors = {};
        const settings = {};
        
        // Collect colors (only if changed)
        ['--accent', '--primary', '--secondary', '--background', '--text'].forEach(key => {
            if (state[key]) colors[key] = state[key];
            [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach(step => {
                const shadeKey = `${key}-${step}`;
                if (state[shadeKey]) colors[shadeKey] = state[shadeKey];
            });
        });
        
        // Border - save as number for rem
        if (state['--border1']) {
            colors['--border1'] = parseFloat(state['--border1']);
        }
        if (state['--border05']) {
            colors['--border05'] = parseFloat(state['--border05']);
        }
        
        // Background
        ['--background-type', '--bg-grad1', '--bg-grad2', '--bg-grad3'].forEach(key => {
            if (state[key]) colors[key] = state[key];
        });
        
        // Container width from attribute
        colors['--container-width'] = state['_container-width'] || 'container';
        
        // Settings - fonts and other CSS variables
        const settingsVars = [
            '--font', '--font-header', '--font-scale', 
            '--space-xs', '--space-sm', '--space-md', '--space-lg', '--space-xl', 
            '--transition', '--blur-amount', '--max-content-width', 
            '--shadow-small', '--shadow-medium', '--shadow-large'
        ];
        
        settingsVars.forEach(key => {
            if (state[key]) {
                const settingKey = key.replace(/^--/, '').replace(/-/g, '_');
                settings[settingKey] = state[key];
            }
        });
        
        this.saveBtn.disabled = true;
        this.saveBtn.classList.add('saving');
        
        try {
            const response = await fetch(u('api/pages/save-theme'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ colors, settings, theme })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.close();
                if (typeof notyf !== 'undefined') notyf.success(translate('page-edit.settings_saved') || 'Saved');
            } else {
                throw new Error(data.error || 'Save failed');
            }
        } catch (error) {
            console.error('Save error:', error);
            if (typeof notyf !== 'undefined') notyf.error(translate('page-edit.settings_error') || 'Error');
        } finally {
            this.saveBtn.disabled = false;
            this.saveBtn.classList.remove('saving');
        }
    }
}

// Initialize
function initVisualEditor() {
    if (!window.visualEditor) {
        window.visualEditor = new VisualEditor();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVisualEditor);
} else {
    initVisualEditor();
}

document.body.addEventListener('htmx:afterSwap', () => setTimeout(initVisualEditor, 50));
