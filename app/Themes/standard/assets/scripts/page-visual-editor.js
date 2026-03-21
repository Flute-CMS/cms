/**
 * Visual Editor - Theme Customization
 */

const GOOGLE_FONTS = [
    'Manrope', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
    'Nunito', 'Raleway', 'Ubuntu', 'Rubik', 'Work Sans', 'DM Sans', 'Outfit',
    'Plus Jakarta Sans', 'Space Grotesk', 'Lexend', 'Sora', 'Urbanist', 'Figtree',
    'Playfair Display', 'Merriweather', 'Lora',
    'Bebas Neue', 'Oswald', 'Fjalla One', 'Archivo Black', 'Bricolage Grotesque',
    'Red Hat Display', 'Unbounded', 'Instrument Sans', 'Gabarito', 'Anybody',
    'Albert Sans', 'Onest', 'Geologica',
    'Quicksand', 'Barlow', 'Barlow Condensed', 'Karla', 'Righteous',
    'Familjen Grotesk', 'Titillium Web', 'Spectral', 'Crimson Text', 'Bungee',
    'Hanken Grotesk', 'Wix Madefor Display', 'Atkinson Hyperlegible', 'Comfortaa'
];

/**
 * Color Presets - branded color palettes for dark & light themes
 * Each preset defines only the 5 base colors per theme mode
 */
const COLOR_PRESETS = {
    standard: {
        name: 'Standard',
        font: 'Manrope', headingFont: 'inherit', radius: 1,
        dark:  { '--accent': '#8AD86A', '--primary': '#e8e8ed', '--secondary': '#1e1e22', '--background': '#121214', '--text': '#e8e8ed' },
        light: { '--accent': '#2EA84D', '--primary': '#1d1d1f', '--secondary': '#f5f5f7', '--background': '#ffffff', '--text': '#1d1d1f' }
    },
    youtube: {
        name: 'YouTube',
        font: 'Roboto', headingFont: 'Oswald', radius: 0.75,
        dark:  { '--accent': '#DA3633', '--primary': '#F1F1F1', '--secondary': '#1F1F1F', '--background': '#0F0F0F', '--text': '#F1F1F1' },
        light: { '--accent': '#CC2D2A', '--primary': '#0F0F0F', '--secondary': '#F2F2F2', '--background': '#FFFFFF', '--text': '#0F0F0F' }
    },
    claude: {
        name: 'Claude',
        font: 'Sora', headingFont: 'inherit', radius: 1.25,
        dark:  { '--accent': '#C4870E', '--primary': '#EEECE7', '--secondary': '#22201D', '--background': '#1A1816', '--text': '#EEECE7' },
        light: { '--accent': '#B37A0A', '--primary': '#2D2B28', '--secondary': '#F5F3EE', '--background': '#FFFBF5', '--text': '#2D2B28' }
    },
    openai: {
        name: 'OpenAI',
        font: 'Albert Sans', headingFont: 'Hanken Grotesk', radius: 1,
        dark:  { '--accent': '#1A8A6A', '--primary': '#ECECEC', '--secondary': '#1A1A1A', '--background': '#0D0D0D', '--text': '#ECECEC' },
        light: { '--accent': '#16795C', '--primary': '#1A1A1A', '--secondary': '#F7F7F8', '--background': '#FFFFFF', '--text': '#1A1A1A' }
    },
    discord: {
        name: 'Discord',
        font: 'DM Sans', headingFont: 'Red Hat Display', radius: 0.5,
        dark:  { '--accent': '#6B74D6', '--primary': '#DBDEE1', '--secondary': '#2B2D31', '--background': '#1E1F22', '--text': '#DBDEE1' },
        light: { '--accent': '#4E58C5', '--primary': '#2E3338', '--secondary': '#F2F3F5', '--background': '#FFFFFF', '--text': '#2E3338' }
    },
    spotify: {
        name: 'Spotify',
        font: 'Figtree', headingFont: 'Bebas Neue', radius: 0.625,
        dark:  { '--accent': '#1A9E49', '--primary': '#F0F0F0', '--secondary': '#1A1A1A', '--background': '#121212', '--text': '#F0F0F0' },
        light: { '--accent': '#178C40', '--primary': '#191414', '--secondary': '#F6F6F6', '--background': '#FFFFFF', '--text': '#191414' }
    },
    github: {
        name: 'GitHub',
        font: 'Inter', headingFont: 'inherit', radius: 0.5,
        dark:  { '--accent': '#4B8FD4', '--primary': '#E6EDF3', '--secondary': '#161B22', '--background': '#0D1117', '--text': '#E6EDF3' },
        light: { '--accent': '#1167B8', '--primary': '#1F2328', '--secondary': '#F6F8FA', '--background': '#FFFFFF', '--text': '#1F2328' }
    },
    linear: {
        name: 'Linear',
        font: 'Instrument Sans', headingFont: 'inherit', radius: 0.625,
        dark:  { '--accent': '#7078C4', '--primary': '#EEEEF0', '--secondary': '#1A1A1F', '--background': '#111113', '--text': '#EEEEF0' },
        light: { '--accent': '#5159AB', '--primary': '#1A1A1A', '--secondary': '#F5F5F7', '--background': '#FBFBFB', '--text': '#1A1A1A' }
    },
    stripe: {
        name: 'Stripe',
        font: 'Plus Jakarta Sans', headingFont: 'Wix Madefor Display', radius: 0.75,
        dark:  { '--accent': '#7A74E0', '--primary': '#EFF4F8', '--secondary': '#0E2A47', '--background': '#0A2540', '--text': '#EFF4F8' },
        light: { '--accent': '#564FD6', '--primary': '#0A2540', '--secondary': '#F6F9FC', '--background': '#FFFFFF', '--text': '#0A2540' }
    },
    notion: {
        name: 'Notion',
        font: 'Inter', headingFont: 'Lora', radius: 0.375,
        dark:  { '--accent': '#C96060', '--primary': '#E0E0E0', '--secondary': '#252525', '--background': '#191919', '--text': '#E0E0E0' },
        light: { '--accent': '#C04A4A', '--primary': '#37352F', '--secondary': '#F7F6F3', '--background': '#FFFFFF', '--text': '#37352F' }
    },
    vercel: {
        name: 'Vercel',
        font: 'Onest', headingFont: 'inherit', radius: 0.5,
        dark:  { '--accent': '#E0E0E0', '--primary': '#EDEDED', '--secondary': '#111111', '--background': '#000000', '--text': '#EDEDED' },
        light: { '--accent': '#1A1A1A', '--primary': '#000000', '--secondary': '#FAFAFA', '--background': '#FFFFFF', '--text': '#000000' }
    },
    dracula: {
        name: 'Dracula',
        font: 'Rubik', headingFont: 'Unbounded', radius: 1,
        dark:  { '--accent': '#A985D6', '--primary': '#F8F8F2', '--secondary': '#343746', '--background': '#282A36', '--text': '#F8F8F2' },
        light: { '--accent': '#8560C0', '--primary': '#282A36', '--secondary': '#F8F8F2', '--background': '#FFFFFF', '--text': '#282A36' }
    },
    nord: {
        name: 'Nord',
        font: 'Nunito', headingFont: 'Comfortaa', radius: 1.125,
        dark:  { '--accent': '#7BB0BE', '--primary': '#ECEFF4', '--secondary': '#3B4252', '--background': '#2E3440', '--text': '#ECEFF4' },
        light: { '--accent': '#527594', '--primary': '#2E3440', '--secondary': '#E5E9F0', '--background': '#ECEFF4', '--text': '#2E3440' }
    },
    rose: {
        name: 'Rosé Pine',
        font: 'Urbanist', headingFont: 'Playfair Display', radius: 1,
        dark:  { '--accent': '#b196d0', '--primary': '#e0def4', '--secondary': '#26233a', '--background': '#191724', '--text': '#e0def4' },
        light: { '--accent': '#7d6a96', '--primary': '#575279', '--secondary': '#f2e9e1', '--background': '#faf4ed', '--text': '#575279' }
    },
    catppuccin: {
        name: 'Catppuccin',
        font: 'Outfit', headingFont: 'Gabarito', radius: 1.25,
        dark:  { '--accent': '#b493d6', '--primary': '#cdd6f4', '--secondary': '#313244', '--background': '#1e1e2e', '--text': '#cdd6f4' },
        light: { '--accent': '#7432c9', '--primary': '#4c4f69', '--secondary': '#e6e9ef', '--background': '#eff1f5', '--text': '#4c4f69' }
    },
    sunset: {
        name: 'Sunset',
        font: 'Lexend', headingFont: 'Oswald', radius: 0.75,
        dark:  { '--accent': '#D4713A', '--primary': '#F5E8DC', '--secondary': '#1C1410', '--background': '#120E0A', '--text': '#F5E8DC' },
        light: { '--accent': '#BF5A22', '--primary': '#2D1F14', '--secondary': '#FFF5EE', '--background': '#FFFAF5', '--text': '#2D1F14' }
    },
    ocean: {
        name: 'Ocean',
        font: 'Space Grotesk', headingFont: 'Bricolage Grotesque', radius: 0.875,
        dark:  { '--accent': '#3A9FB3', '--primary': '#D6EFF3', '--secondary': '#0C2D3E', '--background': '#061E2C', '--text': '#D6EFF3' },
        light: { '--accent': '#0E7A8F', '--primary': '#164E63', '--secondary': '#EBF8FA', '--background': '#F8FFFE', '--text': '#164E63' }
    },
    golden: {
        name: 'Black & Gold',
        font: 'Montserrat', headingFont: 'Playfair Display', radius: 0.375,
        dark:  { '--accent': '#D4AD3A', '--primary': '#F0E6C8', '--secondary': '#0C0A06', '--background': '#05050A', '--text': '#F0E6C8' },
        light: { '--accent': '#A6841C', '--primary': '#1A1608', '--secondary': '#FEFCE8', '--background': '#FFFEF5', '--text': '#1A1608' }
    },
    neon: {
        name: 'Neon',
        font: 'Geologica', headingFont: 'Comfortaa', radius: 1.5,
        dark:  { '--accent': '#3CC9A8', '--primary': '#D6F0EA', '--secondary': '#0D1B2A', '--background': '#060D16', '--text': '#D6F0EA' },
        light: { '--accent': '#1F9B7E', '--primary': '#0D1B2A', '--secondary': '#EDF8F4', '--background': '#FFFFFF', '--text': '#0D1B2A' }
    },
    brutalist: {
        name: 'Brutalist',
        font: 'Anybody', headingFont: 'Archivo Black', radius: 0,
        dark:  { '--accent': '#D44468', '--primary': '#F0F0F0', '--secondary': '#1A1A1A', '--background': '#000000', '--text': '#F0F0F0' },
        light: { '--accent': '#C03558', '--primary': '#0A0A0A', '--secondary': '#F0F0F0', '--background': '#FFFFFF', '--text': '#0A0A0A' }
    },
    gaming: {
        name: 'Gaming',
        font: 'Red Hat Display', headingFont: 'Fjalla One', radius: 0.625,
        dark:  { '--accent': '#8B5CC6', '--primary': '#E8DEFA', '--secondary': '#18102B', '--background': '#0E0820', '--text': '#E8DEFA' },
        light: { '--accent': '#6535A8', '--primary': '#1E103A', '--secondary': '#F3EDFF', '--background': '#FAFAFE', '--text': '#1E103A' }
    },
    vapor: {
        name: 'Vaporwave',
        font: 'Gabarito', headingFont: 'Unbounded', radius: 1.5,
        dark:  { '--accent': '#D480B0', '--primary': '#E0CCEB', '--secondary': '#1A0A2E', '--background': '#0F0620', '--text': '#E0CCEB' },
        light: { '--accent': '#AD3A72', '--primary': '#2D1049', '--secondary': '#F8EDF5', '--background': '#FFF5FC', '--text': '#2D1049' }
    },
    arctic: {
        name: 'Arctic',
        font: 'Hanken Grotesk', headingFont: 'Bricolage Grotesque', radius: 0.75,
        dark:  { '--accent': '#5BA3D4', '--primary': '#E2E8F0', '--secondary': '#1E293B', '--background': '#0F172A', '--text': '#E2E8F0' },
        light: { '--accent': '#1872A8', '--primary': '#0F172A', '--secondary': '#F1F5F9', '--background': '#F8FAFC', '--text': '#0F172A' }
    },
    rust: {
        name: 'Rust',
        font: 'Bricolage Grotesque', headingFont: 'Oswald', radius: 0.5,
        dark:  { '--accent': '#C4600E', '--primary': '#F0DFD0', '--secondary': '#1C1108', '--background': '#120B04', '--text': '#F0DFD0' },
        light: { '--accent': '#A5390E', '--primary': '#1C1108', '--secondary': '#FFF7ED', '--background': '#FFFCF5', '--text': '#1C1108' }
    },
    matrix: {
        name: 'Matrix',
        font: 'Space Grotesk', headingFont: 'Bebas Neue', radius: 0.25,
        dark:  { '--accent': '#3DA65C', '--primary': '#C8EDD8', '--secondary': '#052E16', '--background': '#021208', '--text': '#C8EDD8' },
        light: { '--accent': '#1D7035', '--primary': '#052E16', '--secondary': '#EFF8F2', '--background': '#FAFFFE', '--text': '#052E16' }
    },
    editorial: {
        name: 'Editorial',
        font: 'Familjen Grotesk', headingFont: 'Spectral', radius: 0.25,
        dark:  { '--accent': '#C9A87C', '--primary': '#E8E0D6', '--secondary': '#1E1B18', '--background': '#141210', '--text': '#E8E0D6' },
        light: { '--accent': '#8B6F4E', '--primary': '#2C2520', '--secondary': '#F5F0EB', '--background': '#FDFAF6', '--text': '#2C2520' }
    }
};

class VisualEditor {
    constructor() {
        this.root = document.documentElement;
        this.editor = document.getElementById('visual-editor');
        this.backdrop = document.getElementById('visual-editor-backdrop');
        
        if (!this.editor) return;

        this.historyByTheme = {};
        this.historyIndexByTheme = {};
        this.maxHistory = 30;
        this.isOpen = false;
        this.loadedFonts = new Set(['Manrope']);
        this.initialStates = {};
        this.themeData = window.__themeCustomization || {};
        this.pendingUploads = {};

        this.gradientState = {
            type: 'none',
            angle: 135,
            posX: 50,
            posY: 50,
            intensity: 1,
            stops: [
                { color: '#A5FF75', position: 0, opacity: 100 },
                { color: '#121214', position: 100, opacity: 100 }
            ]
        };
        
        this.init();
    }

    init() {
        this.currentColorPreset = null;
        this.cacheElements();
        this.buildColorPresets();
        this.bindEvents();
        this.bindUploadEvents();
    }

    cacheElements() {
        this.closeBtn = document.getElementById('visual-editor-close');
        this.undoBtn = document.getElementById('ve-undo');
        this.redoBtn = document.getElementById('ve-redo');
        this.resetBtn = document.getElementById('ve-reset');
        this.cancelBtn = document.getElementById('ve-cancel');
        this.saveBtn = document.getElementById('ve-save');
        
        this.colorLabels = this.editor.querySelectorAll('.ve__color[data-variable]');
        this.bgEffectButtons = this.editor.querySelectorAll('.ve__bg-effect');
        this.effectOpacityWrap = document.getElementById('ve-effect-opacity-wrap');
        this.sliders = this.editor.querySelectorAll('.ve__range');
        this.selects = this.editor.querySelectorAll('.ve__select');
        this.borderPreview = document.getElementById('ve-border-preview');
        
        // Gradient editor elements
        this.gradientTypeButtons = this.editor.querySelectorAll('.ve__gradient-type');
        this.gradientEditor = document.getElementById('ve-gradient-editor');
        this.gradientBar = document.getElementById('ve-gradient-bar');
        this.gradientBarTrack = document.getElementById('ve-gradient-bar-track');
        this.gradientAngleSlider = document.getElementById('ve-gradient-angle');
        this.gradientAngleWrap = document.getElementById('ve-gradient-angle-wrap');
        this.gradientPositionWrap = document.getElementById('ve-gradient-position-wrap');
        this.gradientPositionPreview = document.getElementById('ve-gradient-position-preview');
        this.gradientHandle = document.getElementById('ve-gradient-handle');
        this.angleIndicator = document.getElementById('ve-angle-indicator');
        this.angleValue = document.getElementById('ve-angle-value');
        this.addGradientStopBtn = document.getElementById('ve-add-gradient-stop');
        this.gradientIntensitySlider = document.getElementById('ve-gradient-intensity');
        
        // Color stop editor elements
        this.stopEditor = document.getElementById('ve-gradient-stop-editor');
        this.stopColorInput = document.getElementById('ve-stop-color');
        this.stopColorPreview = document.getElementById('ve-stop-color-preview');
        this.stopPositionInput = document.getElementById('ve-stop-position');
        this.stopOpacityInput = document.getElementById('ve-stop-opacity');
        this.stopOpacityVal = document.getElementById('ve-stop-opacity-val');
        this.deleteStopBtn = document.getElementById('ve-delete-gradient-stop');
        
        this.selectedStopIndex = null;
        this.navSocialsToggle = document.getElementById('ve-nav-socials');
        
        // Toggles - the input inside toggle-switch component
        this.fullwidthToggle = document.getElementById('ve-fullwidth');
        // fullwidthLayoutToggle removed - single fullwidth toggle now
        this.shadowsToggle = document.getElementById('ve-shadows');
        this.footerSocialsToggle = document.getElementById('ve-footer-socials');
        this.footerLogoToggle = document.getElementById('ve-footer-logo');
        this.navFixedToggle = document.getElementById('ve-nav-fixed');
        this.navFixedWrap = document.getElementById('ve-nav-fixed-wrap');
        this.navBlurToggle = document.getElementById('ve-nav-blur');
        this.navBlurWrap = document.getElementById('ve-nav-blur-wrap');
        this.hoverScaleToggle = document.getElementById('ve-hover-scale');
        
        // Navigation style cards
        this.navStyleCards = this.editor.querySelectorAll('[data-nav-style]');
        // Sidebar style section and cards
        this.sidebarStylesSection = document.getElementById('ve-sidebar-styles');
        this.sidebarStyleCards = this.editor.querySelectorAll('[data-sidebar-style]');
        // Sidebar mode section and cards
        this.sidebarModeSection = document.getElementById('ve-sidebar-mode');
        this.sidebarModeCards = this.editor.querySelectorAll('[data-sidebar-mode]');
        // Sidebar position section and cards
        this.sidebarPositionSection = document.getElementById('ve-sidebar-position');
        this.sidebarPositionCards = this.editor.querySelectorAll('[data-sidebar-position]');
        // Sidebar contained toggle
        this.sidebarContainedToggle = document.getElementById('ve-sidebar-contained');
        this.sidebarContainedWrap = document.getElementById('ve-sidebar-contained-wrap');
        // Footer type cards
        this.footerTypeCards = this.editor.querySelectorAll('[data-footer-type]');
        
        // Emoji editor elements
        this.emojiEditor = document.getElementById('ve-emoji-editor');
        this.emojiPresets = this.editor.querySelectorAll('.ve__emoji-preset');
        this.emojiCustomWrap = document.getElementById('ve-emoji-custom-wrap');
        this.emojiCustomInput = document.getElementById('ve-emoji-custom');
        this.emojiAngleSlider = document.getElementById('ve-emoji-angle');
        this.emojiAngleVal = document.getElementById('ve-emoji-angle-val');
        this.emojiSizeSlider = document.getElementById('ve-emoji-size');
        this.emojiSizeVal = document.getElementById('ve-emoji-size-val');
        this.emojiSpacingSlider = document.getElementById('ve-emoji-spacing');
        this.emojiSpacingVal = document.getElementById('ve-emoji-spacing-val');
        this.emojiAccentToggle = document.getElementById('ve-emoji-accent');
        
        // Emoji presets (4 emojis for 2x2 grid)
        this.emojiPresetData = {
            stars: ['⭐', '✨', '💫', '🌟'],
            hearts: ['❤️', '💕', '💖', '💗'],
            fire: ['🔥', '💥', '✨', '⚡'],
            gaming: ['🎮', '🕹️', '👾', '🎯'],
            nature: ['🌿', '🍃', '🌸', '🌺'],
            space: ['🚀', '🌙', '⭐', '🪐'],
            custom: []
        };
        
        this.emojiState = {
            preset: 'stars',
            custom: '⭐ ✨ 💫 🌟',
            angle: 0,
            size: 24,
            spacing: 64,
            useAccent: true
        };
        
        this.colorPresetsContainer = document.getElementById('ve-color-presets');
        this.fontCardsContainer = document.getElementById('ve-font-cards');
        this.headingFontCardsContainer = document.getElementById('ve-heading-font-cards');

        this.tabs = this.editor.querySelectorAll('.ve__tab[data-ve-tab]');
        this.panels = this.editor.querySelectorAll('.ve__panel[data-ve-panel]');
    }

    /**
     * Build the color preset chips
     */
    buildColorPresets() {
        if (!this.colorPresetsContainer) return;
        this.colorPresetsContainer.innerHTML = '';

        const theme = this.getThemeKey();

        Object.entries(COLOR_PRESETS).forEach(([key, preset]) => {
            const colors = preset[theme] || preset.dark;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 've__color-preset' + (key === this.currentColorPreset ? ' active' : '');
            btn.dataset.colorPreset = key;
            btn.title = preset.name;

            btn.innerHTML = `
                <span class="ve__color-preset-swatch">
                    <i style="background:${colors['--accent']}"></i>
                    <i style="background:${colors['--background']}"></i>
                    <i style="background:${colors['--text']}"></i>
                    <i style="background:${colors['--secondary']}"></i>
                </span>
                <span class="ve__color-preset-name">${preset.name}</span>
            `;

            btn.addEventListener('click', () => this.applyColorPreset(key));
            this.colorPresetsContainer.appendChild(btn);
        });
    }

    /**
     * Apply a color preset (only changes the 5 base colors)
     */
    applyColorPreset(presetKey) {
        const preset = COLOR_PRESETS[presetKey];
        if (!preset) return;

        const theme = this.getThemeKey();
        const colors = preset[theme] || preset.dark;

        // Apply only the 5 color variables
        Object.entries(colors).forEach(([variable, value]) => {
            this.setProperty(variable, value);
            this.generateShades(variable, value);
        });

        this.currentColorPreset = presetKey;

        // Update active state
        this.colorPresetsContainer?.querySelectorAll('.ve__color-preset').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.colorPreset === presetKey);
        });

        // Refresh color picker UI
        this.colorLabels.forEach(label => {
            const variable = label.dataset.variable;
            const input = label.querySelector('.ve__color-input');
            const preview = label.querySelector('.ve__color-preview');
            if (colors[variable] && input && preview) {
                input.value = tinycolor(colors[variable]).toHexString();
                preview.style.background = colors[variable];
            }
        });

        // Apply border radius from preset
        if (preset.radius !== undefined) {
            const r = preset.radius;
            this.setProperty('--border1', r + 'rem');
            this.setProperty('--border05', (r / 2) + 'rem');
            const slider = document.getElementById('ve-border-radius');
            if (slider) {
                slider.value = r;
                this.updateSliderDisplay(slider, r, 'rem');
            }
            this.updateBorderPreview();
        }

        // Apply paired fonts from preset
        if (preset.font) {
            const bodyFont = preset.font;
            if (bodyFont === 'inherit') {
                this.setProperty('--font', 'inherit');
            } else {
                this.loadFont(bodyFont);
                this.setProperty('--font', `'${bodyFont}', sans-serif`);
            }
            this.updateFontCardState(this.fontCardsContainer, bodyFont);
        }

        if (preset.headingFont) {
            const headingFont = preset.headingFont;
            if (headingFont === 'inherit') {
                this.setProperty('--font-header', 'inherit');
            } else {
                this.loadFont(headingFont);
                this.setProperty('--font-header', `'${headingFont}', sans-serif`);
            }
            this.updateFontCardState(this.headingFontCardsContainer, headingFont);
        }

        this.recordHistory();
    }

    updateFontCardState(container, fontName) {
        if (!container) return;
        container.querySelectorAll('.ve__font-card').forEach(card => {
            const isMatch = card.dataset.font === fontName;
            card.classList.toggle('active', isMatch);
            const radio = card.querySelector('input');
            if (radio) radio.checked = isMatch;
        });
    }

    switchTab(tabKey) {
        this.tabs.forEach(t => t.classList.toggle('active', t.dataset.veTab === tabKey));
        this.panels.forEach(p => p.classList.toggle('active', p.dataset.vePanel === tabKey));
    }

    bindEvents() {
        document.getElementById('page-open-editor')?.addEventListener('click', () => this.open());

        this.closeBtn?.addEventListener('click', () => this.close());
        this.backdrop?.addEventListener('click', () => this.close());
        this.cancelBtn?.addEventListener('click', () => this.cancel());
        this.saveBtn?.addEventListener('click', () => this.save());

        this.tabs.forEach(tab => {
            tab.addEventListener('click', () => this.switchTab(tab.dataset.veTab));
        });

        this.undoBtn?.addEventListener('click', () => this.undo());
        this.redoBtn?.addEventListener('click', () => this.redo());
        this.resetBtn?.addEventListener('click', () => this.reset());
        
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
        
        // Gradient type buttons
        this.gradientTypeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.setGradientType(btn.dataset.gradientType);
                this.recordHistory();
            });
        });
        
        // Gradient angle slider
        this.gradientAngleSlider?.addEventListener('input', (e) => {
            this.setGradientAngle(parseInt(e.target.value));
        });
        this.gradientAngleSlider?.addEventListener('change', () => this.recordHistory());
        
        // Add gradient stop button
        this.addGradientStopBtn?.addEventListener('click', () => {
            this.addGradientStop();
            this.recordHistory();
        });
        
        // Gradient intensity slider
        this.gradientIntensitySlider?.addEventListener('input', (e) => {
            this.setGradientIntensity(parseFloat(e.target.value));
        });
        this.gradientIntensitySlider?.addEventListener('change', () => this.recordHistory());
        
        // Stop editor inputs
        this.stopColorInput?.addEventListener('input', (e) => {
            if (this.selectedStopIndex !== null) {
                this.gradientState.stops[this.selectedStopIndex].color = e.target.value;
                this.renderGradientBar();
                this.updateGradientPreview();
            }
        });
        this.stopColorInput?.addEventListener('change', () => this.recordHistory());
        
        this.stopPositionInput?.addEventListener('input', (e) => {
            if (this.selectedStopIndex !== null) {
                const pos = Math.max(0, Math.min(100, parseInt(e.target.value) || 0));
                this.gradientState.stops[this.selectedStopIndex].position = pos;
                this.gradientState.stops.sort((a, b) => a.position - b.position);
                this.selectedStopIndex = this.gradientState.stops.findIndex(s => s.position === pos);
                this.renderGradientBar();
                this.updateGradientPreview();
            }
        });
        this.stopPositionInput?.addEventListener('change', () => this.recordHistory());
        
        this.deleteStopBtn?.addEventListener('click', () => {
            if (this.selectedStopIndex !== null && this.gradientState.stops.length > 2) {
                this.removeGradientStop(this.selectedStopIndex);
            }
        });
        
        // Stop opacity slider
        this.stopOpacityInput?.addEventListener('input', (e) => {
            if (this.selectedStopIndex !== null) {
                const opacity = parseInt(e.target.value);
                this.gradientState.stops[this.selectedStopIndex].opacity = opacity;
                if (this.stopOpacityVal) this.stopOpacityVal.textContent = `${opacity}%`;
                this.renderGradientBar();
                this.updateGradientPreview();
            }
        });
        this.stopOpacityInput?.addEventListener('change', () => this.recordHistory());
        
        // Initialize gradient bar and position control
        this.initGradientBar();
        this.initGradientHandleDrag();
        this.initAngleWheelDrag();
        
        // Nav socials toggle
        this.navSocialsToggle?.addEventListener('change', (e) => {
            this.handleNavSocialsToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Background effects
        this.bgEffectButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.setBackgroundEffect(btn.dataset.bgEffect);
                this.recordHistory();
            });
        });
        
        // Emoji presets
        this.emojiPresets.forEach(btn => {
            btn.addEventListener('click', () => {
                this.setEmojiPreset(btn.dataset.emojiPreset);
                this.recordHistory();
            });
        });
        
        // Emoji custom input
        this.emojiCustomInput?.addEventListener('input', (e) => {
            this.emojiState.custom = e.target.value;
            this.updateEmojiPattern();
        });
        this.emojiCustomInput?.addEventListener('change', () => this.recordHistory());
        
        // Emoji angle
        this.emojiAngleSlider?.addEventListener('input', (e) => {
            this.emojiState.angle = parseInt(e.target.value);
            if (this.emojiAngleVal) this.emojiAngleVal.textContent = `${this.emojiState.angle}°`;
            this.updateEmojiPattern();
        });
        this.emojiAngleSlider?.addEventListener('change', () => this.recordHistory());
        
        // Emoji size
        this.emojiSizeSlider?.addEventListener('input', (e) => {
            this.emojiState.size = parseInt(e.target.value);
            if (this.emojiSizeVal) this.emojiSizeVal.textContent = `${this.emojiState.size}px`;
            this.updateEmojiPattern();
        });
        this.emojiSizeSlider?.addEventListener('change', () => this.recordHistory());
        
        // Emoji spacing
        this.emojiSpacingSlider?.addEventListener('input', (e) => {
            this.emojiState.spacing = parseInt(e.target.value);
            if (this.emojiSpacingVal) this.emojiSpacingVal.textContent = `${this.emojiState.spacing}px`;
            this.updateEmojiPattern();
        });
        this.emojiSpacingSlider?.addEventListener('change', () => this.recordHistory());
        
        // Emoji accent toggle
        this.emojiAccentToggle?.addEventListener('change', (e) => {
            this.emojiState.useAccent = e.target.checked;
            this.updateEmojiPattern();
            this.recordHistory();
        });
        
        // Sliders
        this.sliders.forEach(slider => {
            slider.addEventListener('input', (e) => this.handleSliderInput(e.target));
            slider.addEventListener('change', () => this.recordHistory());
        });
        
        // Selects (legacy, if any remain)
        this.selects.forEach(select => {
            select.addEventListener('change', (e) => this.handleSelectChange(e.target));
        });

        // Font cards
        [this.fontCardsContainer, this.headingFontCardsContainer].forEach(container => {
            if (!container) return;
            const variable = container.dataset.variable;
            container.querySelectorAll('.ve__font-card').forEach(card => {
                card.addEventListener('click', () => {
                    const fontName = card.dataset.font;
                    container.querySelectorAll('.ve__font-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    card.querySelector('input').checked = true;

                    if (fontName === 'inherit') {
                        this.setProperty(variable, 'inherit');
                    } else {
                        this.loadFont(fontName);
                        this.setProperty(variable, `'${fontName}', sans-serif`);
                    }
                    this.recordHistory();
                });
            });
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
        
        // Hover scale toggle
        this.hoverScaleToggle?.addEventListener('change', (e) => {
            this.handleHoverScaleToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Footer socials toggle
        this.footerSocialsToggle?.addEventListener('change', (e) => {
            this.handleFooterSocialsToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Footer logo toggle
        this.footerLogoToggle?.addEventListener('change', (e) => {
            this.handleFooterLogoToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Navigation style cards
        this.navStyleCards.forEach(card => {
            card.addEventListener('click', () => {
                this.setNavStyle(card.dataset.navStyle);
                this.recordHistory();
            });
        });

        // Sidebar style cards
        this.sidebarStyleCards.forEach(card => {
            card.addEventListener('click', () => {
                this.setSidebarStyle(card.dataset.sidebarStyle);
                this.recordHistory();
            });
        });

        // Sidebar mode cards
        this.sidebarModeCards.forEach(card => {
            card.addEventListener('click', () => {
                this.setSidebarMode(card.dataset.sidebarMode);
                this.recordHistory();
            });
        });

        // Sidebar position cards
        this.sidebarPositionCards.forEach(card => {
            card.addEventListener('click', () => {
                this.setSidebarPosition(card.dataset.sidebarPosition);
                this.recordHistory();
            });
        });

        // Sidebar contained toggle
        this.sidebarContainedToggle?.addEventListener('change', (e) => {
            this.setSidebarContained(e.target.checked);
            this.recordHistory();
        });

        // Navigation fixed toggle
        this.navFixedToggle?.addEventListener('change', (e) => {
            this.handleNavFixedToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Navigation blur toggle
        this.navBlurToggle?.addEventListener('change', (e) => {
            this.handleNavBlurToggle(e.target.checked);
            this.recordHistory();
        });
        
        // Footer type cards
        this.footerTypeCards.forEach(card => {
            card.addEventListener('click', () => {
                this.setFooterType(card.dataset.footerType);
                this.recordHistory();
            });
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
                    const theme = this.getThemeKey();
                    if (!this.initialStates[theme]) {
                        this.initialStates[theme] = this.captureState();
                    }
                    if (!this.historyByTheme[theme] || this.historyByTheme[theme].length === 0) {
                        this.recordHistory();
                    } else {
                        this.updateHistoryButtons();
                    }
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

        // Preload fonts for card previews
        this.initFontCardPreviews();

        this.loadCurrentValues();
        const theme = this.getThemeKey();
        this.initialStates[theme] = this.captureState();
        this.historyByTheme[theme] = [];
        this.historyIndexByTheme[theme] = -1;
        this.recordHistory();
    }

    close() {
        this.isOpen = false;
        this.editor.classList.remove('open');
        document.body.classList.remove('ve-open');
        document.getElementById('page-edit-fab')?.classList.remove('hide');
    }

    cancel() {
        const theme = this.getThemeKey();
        if (this.initialStates[theme]) {
            this.applyState(this.initialStates[theme]);
        }
        this.close();
    }

    // Load current values
    loadCurrentValues() {
        const computed = getComputedStyle(this.root);
        const themeData = this.getThemeData();
        
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
        
        const storedStops = Array.isArray(themeData?.gradient_stops) ? themeData.gradient_stops : null;
        const dataStopsRaw = this.root.getAttribute('data-gradient-stops');
        let parsedStops = storedStops;

        if (!parsedStops && dataStopsRaw) {
            try {
                const fromAttr = JSON.parse(dataStopsRaw);
                if (Array.isArray(fromAttr)) parsedStops = fromAttr;
            } catch (e) {}
        }

        if (Array.isArray(parsedStops) && parsedStops.length >= 2) {
            this.gradientState.stops = parsedStops.map(stop => {
                const posX = parseFloat(stop.posX);
                const posY = parseFloat(stop.posY);

                return {
                    color: stop.color || '#000000',
                    position: parseInt(stop.position) || 0,
                    opacity: typeof stop.opacity !== 'undefined' ? parseInt(stop.opacity) : 100,
                    posX: Number.isFinite(posX) ? posX : undefined,
                    posY: Number.isFinite(posY) ? posY : undefined
                };
            }).sort((a, b) => a.position - b.position);
        }
        
        // Gradient settings - load values into state without triggering updates
        const gradientAngle = parseInt(computed.getPropertyValue('--gradient-angle')) || 135;
        const gradientPosX = parseInt(computed.getPropertyValue('--gradient-pos-x')) || 50;
        const gradientPosY = parseInt(computed.getPropertyValue('--gradient-pos-y')) || 50;
        let gradientIntensity = parseFloat(computed.getPropertyValue('--gradient-intensity'));
        if (isNaN(gradientIntensity) && themeData?.vars?.['--gradient-intensity']) {
            gradientIntensity = parseFloat(themeData.vars['--gradient-intensity']);
        }
        if (isNaN(gradientIntensity)) gradientIntensity = 0.15;
        
        this.gradientState.angle = gradientAngle;
        this.gradientState.posX = gradientPosX;
        this.gradientState.posY = gradientPosY;
        this.gradientState.intensity = gradientIntensity;
        
        if (this.gradientAngleSlider) this.gradientAngleSlider.value = gradientAngle;
        if (this.gradientIntensitySlider) this.gradientIntensitySlider.value = gradientIntensity;
        if (this.angleIndicator) {
            this.angleIndicator.style.transform = `translateX(-50%) rotate(${gradientAngle}deg)`;
        }
        if (this.angleValue) {
            this.angleValue.textContent = `${gradientAngle}°`;
        }
        if (this.gradientHandle) {
            this.gradientHandle.style.left = `${gradientPosX}%`;
            this.gradientHandle.style.top = `${gradientPosY}%`;
        }
        const intensityValSpan = this.gradientIntensitySlider?.parentElement?.querySelector('.ve__range-val');
        if (intensityValSpan) intensityValSpan.textContent = gradientIntensity.toFixed(2);
        
        const gradientType = this.root.getAttribute('data-gradient-type') || 'none';
        this.gradientState.type = gradientType;
        
        this.gradientTypeButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.gradientType === gradientType));
        if (this.gradientEditor) {
            this.gradientEditor.hidden = gradientType === 'none';
        }
        if (this.gradientAngleWrap) {
            this.gradientAngleWrap.hidden = !(gradientType === 'linear' || gradientType === 'conic');
        }
        if (this.gradientPositionWrap) {
            this.gradientPositionWrap.hidden = !(gradientType === 'radial' || gradientType === 'conic');
        }
        
        // Render gradient bar with loaded stops
        this.renderGradientBar();
        
        // Load emoji settings FIRST before setting background effect
        let emojiSettings = themeData?.emoji_settings;
        if (!emojiSettings) {
            const savedEmojiSettings = this.root.getAttribute('data-emoji-settings');
            if (savedEmojiSettings) {
                try {
                    emojiSettings = JSON.parse(savedEmojiSettings);
                } catch (e) {}
            }
        }

        if (emojiSettings) {
            try {
                this.emojiState = { ...this.emojiState, ...emojiSettings };
            } catch (e) {}
        }
        
        // Update emoji UI elements without triggering pattern update
        this.emojiPresets.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.emojiPreset === this.emojiState.preset);
        });
        if (this.emojiCustomWrap) {
            this.emojiCustomWrap.hidden = this.emojiState.preset !== 'custom';
        }
        if (this.emojiAngleSlider) {
            this.emojiAngleSlider.value = this.emojiState.angle;
            if (this.emojiAngleVal) this.emojiAngleVal.textContent = `${this.emojiState.angle}°`;
        }
        if (this.emojiSizeSlider) {
            this.emojiSizeSlider.value = this.emojiState.size;
            if (this.emojiSizeVal) this.emojiSizeVal.textContent = `${this.emojiState.size}px`;
        }
        if (this.emojiSpacingSlider) {
            this.emojiSpacingSlider.value = this.emojiState.spacing;
            if (this.emojiSpacingVal) this.emojiSpacingVal.textContent = `${this.emojiState.spacing}px`;
        }
        if (this.emojiAccentToggle) {
            this.emojiAccentToggle.checked = this.emojiState.useAccent;
        }
        if (this.emojiCustomInput) {
            this.emojiCustomInput.value = this.emojiState.custom || '';
        }
        
        // NOW set background effect (after emoji settings are loaded)
        const bgEffect = this.root.getAttribute('data-bg-effect') || 'none';
        this.bgEffectButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.bgEffect === bgEffect));
        if (this.effectOpacityWrap) {
            this.effectOpacityWrap.hidden = bgEffect === 'none';
        }
        if (this.emojiEditor) {
            this.emojiEditor.hidden = bgEffect !== 'emoji';
        }
        
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
        
        // Selects (legacy, if any remain)
        this.selects.forEach(select => {
            const variable = select.dataset.variable;
            const value = computed.getPropertyValue(variable).trim();
            if (value) {
                const fontName = value.replace(/['"]/g, '').split(',')[0].trim();
                const option = Array.from(select.options).find(o => o.value === fontName);
                if (option) {
                    select.value = fontName;
                }
            }
        });

        // Font cards
        [this.fontCardsContainer, this.headingFontCardsContainer].forEach(container => {
            if (!container) return;
            const variable = container.dataset.variable;
            const value = computed.getPropertyValue(variable).trim();
            if (value) {
                const fontName = value === 'inherit' ? 'inherit' : value.replace(/['"]/g, '').split(',')[0].trim();
                container.querySelectorAll('.ve__font-card').forEach(card => {
                    const isActive = card.dataset.font === fontName;
                    card.classList.toggle('active', isActive);
                    card.querySelector('input').checked = isActive;
                });
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
        
        // Hover scale toggle
        if (this.hoverScaleToggle) {
            const hasHoverScale = this.root.getAttribute('data-hover-scale') !== 'false';
            this.hoverScaleToggle.checked = hasHoverScale;
        }
        
        // Navigation style
        const navStyle = this.root.getAttribute('data-nav-style') || 'default';
        this.navStyleCards.forEach(card => {
            card.classList.toggle('active', card.dataset.navStyle === navStyle);
        });

        // Show/hide sidebar style section
        if (this.sidebarStylesSection) {
            this.sidebarStylesSection.hidden = navStyle !== 'sidebar';
        }

        // Show/hide nav fixed toggle
        const navFixedWrap = document.getElementById('ve-nav-fixed-wrap');
        if (navFixedWrap) {
            navFixedWrap.hidden = false;
        }

        // Show/hide nav blur toggle (hide for sidebar)
        const navBlurWrap = document.getElementById('ve-nav-blur-wrap');
        if (navBlurWrap) {
            navBlurWrap.hidden = false;
        }

        // Sidebar style
        const sidebarStyle = this.root.getAttribute('data-sidebar-style') || 'default';
        this.sidebarStyleCards.forEach(card => {
            card.classList.toggle('active', card.dataset.sidebarStyle === sidebarStyle);
        });

        // Show/hide sidebar mode section (only for default style)
        if (this.sidebarModeSection) {
            this.sidebarModeSection.hidden = sidebarStyle !== 'default';
        }

        // Sidebar mode
        const sidebarMode = this.root.getAttribute('data-sidebar-mode') || 'full';
        this.sidebarModeCards.forEach(card => {
            card.classList.toggle('active', card.dataset.sidebarMode === sidebarMode);
        });

        // Show/hide sidebar position section (only for mini style)
        if (this.sidebarPositionSection) {
            this.sidebarPositionSection.hidden = sidebarStyle !== 'mini';
        }

        // Sidebar position
        const sidebarPosition = this.root.getAttribute('data-sidebar-position') || 'top';
        this.sidebarPositionCards.forEach(card => {
            card.classList.toggle('active', card.dataset.sidebarPosition === sidebarPosition);
        });

        // Sidebar contained
        if (this.sidebarContainedToggle) {
            this.sidebarContainedToggle.checked = this.root.getAttribute('data-sidebar-contained') === 'true';
        }

        // Navigation fixed toggle
        if (this.navFixedToggle) {
            const isFixed = this.root.getAttribute('data-nav-fixed') !== 'false';
            this.navFixedToggle.checked = isFixed;
        }
        
        // Navigation blur toggle
        if (this.navBlurToggle) {
            const hasBlur = this.root.getAttribute('data-nav-blur') !== 'false';
            this.navBlurToggle.checked = hasBlur;
        }
        
        // Navigation socials toggle
        if (this.navSocialsToggle) {
            const showNavSocials = this.root.getAttribute('data-nav-socials') !== 'false';
            this.navSocialsToggle.checked = showNavSocials;
        }
        
        // Footer type
        const footerType = this.root.getAttribute('data-footer-type') || 'default';
        this.footerTypeCards.forEach(card => {
            card.classList.toggle('active', card.dataset.footerType === footerType);
        });
        
        // Footer socials toggle
        if (this.footerSocialsToggle) {
            const showSocials = this.root.getAttribute('data-footer-socials') !== 'false';
            this.footerSocialsToggle.checked = showSocials;
        }
        
        // Footer logo toggle
        if (this.footerLogoToggle) {
            const showLogo = this.root.getAttribute('data-footer-logo') !== 'false';
            this.footerLogoToggle.checked = showLogo;
        }
        
        // Rebuild color presets (swatches depend on current theme)
        this.buildColorPresets();

        // Border preview
        this.updateBorderPreview();
    }

    // Helpers
    getThemeKey() {
        return this.root.getAttribute('data-theme') || 'dark';
    }

    getThemeData(create = false) {
        const theme = this.getThemeKey();
        if (!this.themeData) return null;

        if (!this.themeData[theme] && create) {
            this.themeData[theme] = { vars: {}, attrs: {}, gradient_stops: [], emoji_settings: {} };
        }

        return this.themeData[theme];
    }

    getThemeHistory() {
        const theme = this.getThemeKey();
        if (!this.historyByTheme[theme]) {
            this.historyByTheme[theme] = [];
            this.historyIndexByTheme[theme] = -1;
        }

        return { theme, history: this.historyByTheme[theme] };
    }

    setThemeVar(variable, value) {
        const data = this.getThemeData(true);
        if (!data) return;
        if (!data.vars) data.vars = {};

        if (value === null || value === undefined || value === '') {
            delete data.vars[variable];
        } else {
            data.vars[variable] = value;
        }
    }

    removeThemeVar(variable) {
        this.setThemeVar(variable, null);
    }

    setThemeAttr(attr, value) {
        const data = this.getThemeData(true);
        if (!data) return;
        if (!data.attrs) data.attrs = {};
        data.attrs[attr] = String(value);
    }

    syncGradientStops() {
        const data = this.getThemeData(true);
        if (!data) return;

        data.gradient_stops = this.gradientState.stops.map(stop => ({
            color: stop.color,
            position: stop.position,
            opacity: stop.opacity ?? 100,
            posX: Number.isFinite(stop.posX) ? stop.posX : undefined,
            posY: Number.isFinite(stop.posY) ? stop.posY : undefined
        }));

        this.root.setAttribute('data-gradient-stops', JSON.stringify(data.gradient_stops));
    }

    syncEmojiSettings() {
        const data = this.getThemeData(true);
        if (!data) return;

        data.emoji_settings = { ...this.emojiState };
        this.root.setAttribute('data-emoji-settings', JSON.stringify(data.emoji_settings));
    }

    getProperty(variable) {
        return getComputedStyle(this.root).getPropertyValue(variable).trim();
    }

    setProperty(variable, value) {
        this.root.style.setProperty(variable, value);
        this.setThemeVar(variable, value);
    }

    removeProperty(variable) {
        this.root.style.removeProperty(variable);
        this.removeThemeVar(variable);
    }

    // Color handling
    updateColor(variable, color, preview) {
        const hex = tinycolor(color).toHexString();
        this.setProperty(variable, hex);
        if (preview) preview.style.background = hex;
        
        this.generateShades(variable, hex);
        
        if (['--accent', '--primary', '--background'].includes(variable)) {
            this.updateGradientPreview();
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

    // Gradient Type
    setGradientType(type, updatePreview = true) {
        this.gradientState.type = type;
        this.setProperty('--gradient-type', type);
        this.root.setAttribute('data-gradient-type', type);
        this.setThemeAttr('gradient-type', type);
        
        this.gradientTypeButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.gradientType === type));
        
        if (this.gradientEditor) {
            this.gradientEditor.hidden = type === 'none';
        }
        
        if (this.gradientAngleWrap) {
            this.gradientAngleWrap.hidden = !(type === 'linear' || type === 'conic');
        }
        
        // Show/hide position control (for radial/conic)
        if (this.gradientPositionWrap) {
            this.gradientPositionWrap.hidden = !(type === 'radial' || type === 'conic');
        }
        
        // Render gradient bar
        if (type !== 'none') {
            this.renderGradientBar();
        }
        
        if (updatePreview) this.updateGradientPreview();
    }
    
    setGradientAngle(angle, updatePreview = true) {
        this.gradientState.angle = angle;
        this.setProperty('--gradient-angle', `${angle}deg`);
        
        if (this.angleIndicator) {
            this.angleIndicator.style.transform = `translateX(-50%) rotate(${angle}deg)`;
        }
        
        if (this.angleValue) {
            this.angleValue.textContent = `${angle}°`;
        }
        
        if (updatePreview) this.updateGradientPreview();
    }
    
    setGradientPosition(x, y, updatePreview = true) {
        if (this.gradientState.type === 'radial' && this.selectedStopIndex !== null) {
            const stop = this.gradientState.stops[this.selectedStopIndex];
            stop.posX = x;
            stop.posY = y;
            this.syncGradientStops();
        } else {
            this.gradientState.posX = x;
            this.gradientState.posY = y;
            this.setProperty('--gradient-pos-x', `${x}%`);
            this.setProperty('--gradient-pos-y', `${y}%`);
        }
        
        if (this.gradientHandle) {
            this.gradientHandle.style.left = `${x}%`;
            this.gradientHandle.style.top = `${y}%`;
        }
        
        if (updatePreview) this.updateGradientPreview();
    }
    
    setGradientIntensity(intensity, updatePreview = true) {
        this.gradientState.intensity = intensity;
        this.setProperty('--gradient-intensity', intensity);
        
        const valSpan = this.gradientIntensitySlider?.parentElement?.querySelector('.ve__range-val');
        if (valSpan) valSpan.textContent = intensity.toFixed(2);
        
        this.renderGradientBar();
        if (updatePreview) this.updateGradientPreview();
    }
    
    // Initialize the visual gradient bar
    initGradientBar() {
        if (!this.gradientBar) return;
        
        // Click on bar to add stop at position
        this.gradientBar.addEventListener('click', (e) => {
            if (e.target.classList.contains('ve__gradient-bar-stop')) return;
            
            const rect = this.gradientBar.getBoundingClientRect();
            const position = Math.round(((e.clientX - rect.left) / rect.width) * 100);
            
            // Deselect current stop
            this.selectStop(null);
        });
        
        this.renderGradientBar();
    }
    
    renderGradientBar() {
        if (!this.gradientBar || !this.gradientBarTrack) return;
        
        const intensity = Math.max(0, Math.min(1, this.gradientState.intensity ?? 1));
        const trackStops = this.gradientState.stops.map(s => {
            const opacity = ((s.opacity ?? 100) / 100) * intensity;
            const tc = tinycolor(s.color);
            return `${tc.setAlpha(opacity).toRgbString()} ${s.position}%`;
        }).join(', ');
        this.gradientBarTrack.style.background = `linear-gradient(90deg, ${trackStops})`;
        
        // Remove existing stop handles
        this.gradientBar.querySelectorAll('.ve__gradient-bar-stop').forEach(el => el.remove());
        
        // Create stop handles
        this.gradientState.stops.forEach((stop, index) => {
            const handle = document.createElement('div');
            handle.className = 've__gradient-bar-stop';
            handle.style.left = `${stop.position}%`;
            handle.style.background = stop.color;
            handle.dataset.index = index;
            
            if (this.selectedStopIndex === index) {
                handle.classList.add('active');
            }
            
            // Click to select
            handle.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectStop(index);
            });
            
            // Drag functionality
            let isDragging = false;
            
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                isDragging = true;
                this.selectStop(index);
                handle.style.cursor = 'grabbing';
            });
            
            const onMove = (e) => {
                if (!isDragging) return;
                const rect = this.gradientBar.getBoundingClientRect();
                let pos = Math.round(((e.clientX - rect.left) / rect.width) * 100);
                pos = Math.max(0, Math.min(100, pos));
                
                this.gradientState.stops[index].position = pos;
                handle.style.left = `${pos}%`;
                
                // Update position input
                if (this.stopPositionInput) {
                    this.stopPositionInput.value = pos;
                }
                
                // Update track gradient
                const sortedStops = [...this.gradientState.stops].sort((a, b) => a.position - b.position);
                const stopsStr = sortedStops.map(s => {
                    const opacity = ((s.opacity ?? 100) / 100) * intensity;
                    return `${tinycolor(s.color).setAlpha(opacity).toRgbString()} ${s.position}%`;
                }).join(', ');
                this.gradientBarTrack.style.background = `linear-gradient(90deg, ${stopsStr})`;
                
                this.updateGradientPreview();
            };
            
            const onUp = () => {
                if (isDragging) {
                    isDragging = false;
                    handle.style.cursor = 'grab';
                    
                    // Sort stops by position
                    this.gradientState.stops.sort((a, b) => a.position - b.position);
                    this.selectedStopIndex = this.gradientState.stops.findIndex(s => s === this.gradientState.stops.find(st => st.color === handle.style.backgroundColor || st.position === parseInt(handle.style.left)));
                    this.renderGradientBar();
                    this.recordHistory();
                }
            };
            
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
            
            this.gradientBar.appendChild(handle);
        });
    }
    
    selectStop(index) {
        this.selectedStopIndex = index;
        
        // Update UI
        this.gradientBar?.querySelectorAll('.ve__gradient-bar-stop').forEach((el, i) => {
            el.classList.toggle('active', i === index);
        });
        
        if (index !== null && this.stopEditor) {
            this.stopEditor.hidden = false;
            const stop = this.gradientState.stops[index];
            if (this.stopColorInput) {
                this.stopColorInput.value = stop.color;
            }
            if (this.stopColorPreview) {
                this.stopColorPreview.style.background = stop.color;
            }
            if (this.stopPositionInput) {
                this.stopPositionInput.value = stop.position;
            }
            if (this.stopOpacityInput) {
                const opacity = stop.opacity ?? 100;
                this.stopOpacityInput.value = opacity;
                if (this.stopOpacityVal) this.stopOpacityVal.textContent = `${opacity}%`;
            }
            if (this.deleteStopBtn) {
                this.deleteStopBtn.disabled = this.gradientState.stops.length <= 2;
            }

            if (this.gradientState.type === 'radial') {
                const posX = Number.isFinite(stop.posX) ? stop.posX : this.gradientState.posX;
                const posY = Number.isFinite(stop.posY) ? stop.posY : this.gradientState.posY;
                if (this.gradientHandle) {
                    this.gradientHandle.style.left = `${posX}%`;
                    this.gradientHandle.style.top = `${posY}%`;
                }
            }
        } else if (this.stopEditor) {
            this.stopEditor.hidden = true;
        }
    }
    
    addGradientStop() {
        if (this.gradientState.stops.length >= 6) return;
        
        // Find the largest gap between stops
        const sortedStops = [...this.gradientState.stops].sort((a, b) => a.position - b.position);
        let maxGap = 0;
        let gapStart = 0;
        let gapEnd = 100;
        
        for (let i = 0; i < sortedStops.length - 1; i++) {
            const gap = sortedStops[i + 1].position - sortedStops[i].position;
            if (gap > maxGap) {
                maxGap = gap;
                gapStart = sortedStops[i].position;
                gapEnd = sortedStops[i + 1].position;
            }
        }
        
        const newPos = Math.round((gapStart + gapEnd) / 2);
        
        // Blend colors from adjacent stops
        const startStop = sortedStops.find(s => s.position <= newPos);
        const endStop = sortedStops.find(s => s.position >= newPos);
        const newColor = startStop ? startStop.color : '#888888';
        
        this.gradientState.stops.push({ color: newColor, position: newPos, opacity: 100 });
        this.gradientState.stops.sort((a, b) => a.position - b.position);
        
        const newIndex = this.gradientState.stops.findIndex(s => s.position === newPos);
        this.renderGradientBar();
        this.selectStop(newIndex);
        this.updateGradientPreview();
    }
    
    removeGradientStop(index) {
        if (this.gradientState.stops.length <= 2) return;
        this.gradientState.stops.splice(index, 1);
        this.selectStop(null);
        this.renderGradientBar();
        this.updateGradientPreview();
        this.recordHistory();
    }
    
    initGradientHandleDrag() {
        if (!this.gradientHandle || !this.gradientPositionPreview) return;
        
        let isDragging = false;
        
        const onMove = (e) => {
            if (!isDragging) return;
            const rect = this.gradientPositionPreview.getBoundingClientRect();
            const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
            this.setGradientPosition(Math.round(x), Math.round(y));
        };
        
        this.gradientHandle.addEventListener('mousedown', (e) => {
            isDragging = true;
            e.preventDefault();
        });
        
        this.gradientPositionPreview.addEventListener('click', (e) => {
            const rect = this.gradientPositionPreview.getBoundingClientRect();
            const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
            this.setGradientPosition(Math.round(x), Math.round(y));
            this.recordHistory();
        });
        
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                this.recordHistory();
            }
        });
    }
    
    initAngleWheelDrag() {
        const wheel = document.getElementById('ve-angle-wheel');
        if (!wheel) return;
        
        let isDragging = false;
        
        const getAngle = (e) => {
            const rect = wheel.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const rad = Math.atan2(e.clientY - centerY, e.clientX - centerX);
            let deg = (rad * 180 / Math.PI) + 90;
            if (deg < 0) deg += 360;
            return Math.round(deg);
        };
        
        wheel.addEventListener('mousedown', (e) => {
            isDragging = true;
            const angle = getAngle(e);
            this.setGradientAngle(angle);
            if (this.gradientAngleSlider) this.gradientAngleSlider.value = angle;
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const angle = getAngle(e);
            this.setGradientAngle(angle);
            if (this.gradientAngleSlider) this.gradientAngleSlider.value = angle;
        });
        
        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                this.recordHistory();
            }
        });
    }
    
    updateGradientPreview() {
        const { type, angle, posX, posY, stops } = this.gradientState;
        
        const intensity = Math.max(0, Math.min(1, this.gradientState.intensity ?? 1));

        if (type === 'none' || stops.length < 2) {
            this.setProperty('--page-gradient', 'none');
            this.syncGradientStops();
            return;
        }
        
        // Build gradient stops with individual opacity for each color
        const gradientStops = stops.map(s => {
            const opacity = ((s.opacity ?? 100) / 100) * intensity;
            const tc = tinycolor(s.color);
            const rgba = tc.setAlpha(opacity).toRgbString();
            return `${rgba} ${s.position}%`;
        }).join(', ');
        
        let gradient;
        switch (type) {
            case 'linear':
                gradient = `linear-gradient(${angle}deg, ${gradientStops})`;
                break;
            case 'radial': {
                const hasStopCenters = stops.some(stop => Number.isFinite(stop.posX) || Number.isFinite(stop.posY));
                if (hasStopCenters) {
                    const layers = stops.map(stop => {
                        const x = Number.isFinite(stop.posX) ? stop.posX : posX;
                        const y = Number.isFinite(stop.posY) ? stop.posY : posY;
                        const radius = Math.max(0, Math.min(100, stop.position ?? 0));
                        const fade = Math.min(100, radius + 25);
                        const opacity = ((stop.opacity ?? 100) / 100) * intensity;
                        const color = tinycolor(stop.color).setAlpha(opacity).toRgbString();
                        return `radial-gradient(circle at ${x}% ${y}%, ${color} 0%, ${color} ${radius}%, rgba(0,0,0,0) ${fade}%)`;
                    });
                    gradient = layers.join(', ');
                } else {
                    gradient = `radial-gradient(circle at ${posX}% ${posY}%, ${gradientStops})`;
                }
                break;
            }
            case 'conic':
                gradient = `conic-gradient(from ${angle}deg at ${posX}% ${posY}%, ${gradientStops})`;
                break;
            default:
                gradient = '';
        }
        
        this.setProperty('--page-gradient', gradient || 'none');
        this.syncGradientStops();
    }
    
    // Navigation socials toggle
    handleNavSocialsToggle(show) {
        this.root.setAttribute('data-nav-socials', show ? 'true' : 'false');
        this.setProperty('--nav-socials', show ? 'true' : 'false');
        this.setThemeAttr('nav-socials', show ? 'true' : 'false');
    }
    
    // Background effect
    setBackgroundEffect(effect, updatePreview = true) {
        this.setProperty('--bg-effect', effect);
        this.root.setAttribute('data-bg-effect', effect);
        this.setThemeAttr('bg-effect', effect);
        
        this.bgEffectButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.bgEffect === effect));
        
        if (this.effectOpacityWrap) {
            this.effectOpacityWrap.hidden = effect === 'none';
        }
        
        // Show/hide emoji editor
        if (this.emojiEditor) {
            this.emojiEditor.hidden = effect !== 'emoji';
        }
        
        if (effect === 'emoji') {
            this.updateEmojiPattern();
        }
        
        if (updatePreview) this.updateBackgroundEffectPreview();
    }
    
    updateBackgroundEffectPreview() {
        const effect = this.root.getAttribute('data-bg-effect') || 'none';
        this.root.setAttribute('data-bg-effect', effect);
    }
    
    // Emoji pattern methods
    setEmojiPreset(preset, updatePattern = true) {
        this.emojiState.preset = preset;
        
        this.emojiPresets.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.emojiPreset === preset);
        });
        
        // Show/hide custom input
        if (this.emojiCustomWrap) {
            this.emojiCustomWrap.hidden = preset !== 'custom';
        }
        
        if (updatePattern) this.updateEmojiPattern();
    }
    
    getEmojiList() {
        if (this.emojiState.preset === 'custom') {
            // Parse custom emojis from input
            return this.emojiState.custom.split(/\s+/).filter(e => e.length > 0);
        }
        return this.emojiPresetData[this.emojiState.preset] || this.emojiPresetData.stars;
    }
    
    updateEmojiPattern() {
        const emojis = this.getEmojiList();
        const { angle, size, spacing, useAccent } = this.emojiState;
        
        // Set CSS variables for emoji pattern
        this.setProperty('--emoji-angle', `${angle}deg`);
        this.setProperty('--emoji-size', `${size}px`);
        this.setProperty('--emoji-spacing', `${spacing}px`);
        
        // Create a grid pattern - emojis in a 2-column grid
        let emojiTexts = '';
        const halfSize = spacing / 2;
        emojis.forEach((emoji, i) => {
            const row = Math.floor(i / 2);
            const col = i % 2;
            const x = col * spacing + halfSize;
            const y = row * spacing + halfSize;
            emojiTexts += `<text x="${x}" y="${y}" font-size="${size}" transform="rotate(${angle} ${x} ${y})" dominant-baseline="middle" text-anchor="middle">${emoji}</text>`;
        });
        
        const rows = Math.ceil(emojis.length / 2);
        const svgWidth = spacing * 2;
        const svgHeight = spacing * rows;
        
        const opacityVal = parseFloat(getComputedStyle(this.root).getPropertyValue('--bg-effect-opacity')) || 0.15;
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${svgWidth}" height="${svgHeight}" viewBox="0 0 ${svgWidth} ${svgHeight}"><g opacity="${opacityVal}">${emojiTexts}</g></svg>`;
        
        if (useAccent) {
            const accentColor = getComputedStyle(this.root).getPropertyValue('--accent').trim();
            if (accentColor) {
                const filter = this.calculateColorFilter(accentColor);
                this.setProperty('--emoji-accent-filter', filter);
            } else {
                this.setProperty('--emoji-accent-filter', 'none');
            }
        } else {
            this.setProperty('--emoji-accent-filter', 'none');
        }
        
        this.syncEmojiSettings();
        
        const encodedSvg = encodeURIComponent(svg);
        const dataUri = `url("data:image/svg+xml,${encodedSvg}")`;
        
        // Apply to body::before via CSS variable
        this.setProperty('--emoji-pattern', dataUri);
        this.setProperty('--emoji-tile-width', `${svgWidth}px`);
        this.setProperty('--emoji-tile-height', `${svgHeight}px`);
    }

    mix(c1, c2, amount) {
        return tinycolor.mix(c2, c1, amount * 100).toHexString();
    }
    
    // Calculate CSS filter to transform black/white to target color
    // Uses the approach: brightness(0) saturate(100%) invert() sepia() saturate() hue-rotate() brightness() contrast()
    calculateColorFilter(targetColor) {
        const tc = tinycolor(targetColor);
        const hsl = tc.toHsl();
        
        // Convert hue to degrees (0-360)
        const hue = Math.round(hsl.h);
        // Saturation as percentage
        const saturation = Math.round(hsl.s * 100);
        // Lightness as percentage  
        const lightness = Math.round(hsl.l * 100);
        
        // For emojis, we need to:
        // 1. Convert to grayscale first
        // 2. Apply sepia to get a base color
        // 3. Rotate hue to target color
        // 4. Adjust saturation and brightness
        
        // Calculate hue rotation from sepia base (which is ~30deg yellow-orange)
        const hueRotate = hue - 30;
        
        // Brightness adjustment based on lightness
        const brightness = 0.5 + (lightness / 100);
        
        // Build the filter
        // grayscale(1) - remove original colors
        // sepia(1) - add sepia tone as base
        // saturate() - control color intensity
        // hue-rotate() - shift to target hue
        // brightness() - adjust lightness
        return `grayscale(1) sepia(1) saturate(${Math.max(100, saturation * 3)}%) hue-rotate(${hueRotate}deg) brightness(${brightness.toFixed(2)})`;
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
        
        if (variable === '--bg-effect-opacity') {
            const bgEffect = this.root.getAttribute('data-bg-effect');
            if (bgEffect === 'emoji') {
                this.updateEmojiPattern();
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
            this.setProperty(variable, 'inherit');
        } else {
            this.loadFont(value);
            this.setProperty(variable, `'${value}'`);
        }
        this.recordHistory();
    }

    // Fullwidth toggle
    handleFullwidthToggle(checked) {
        const mode = checked ? 'fullwidth' : 'container';
        this.setProperty('--container-width', mode);
        this.root.setAttribute('data-container-width', mode);
        this.setThemeAttr('container-width', mode);

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
    
    // Hover scale toggle
    handleHoverScaleToggle(enabled) {
        this.root.setAttribute('data-hover-scale', enabled ? 'true' : 'false');
        this.setProperty('--hover-scale', enabled ? 'true' : 'false');
        this.setThemeAttr('hover-scale', enabled ? 'true' : 'false');
    }
    
    // Navigation style
    setNavStyle(style) {
        this.root.setAttribute('data-nav-style', style);
        this.setProperty('--nav-style', style);
        this.setThemeAttr('nav-style', style);

        this.navStyleCards.forEach(card => {
            card.classList.toggle('active', card.dataset.navStyle === style);
        });

        if (this.sidebarStylesSection) {
            this.sidebarStylesSection.hidden = style !== 'sidebar';
        }

        if (this.sidebarContainedWrap) {
            this.sidebarContainedWrap.hidden = style !== 'sidebar';
        }

        const navFixedWrap = document.getElementById('ve-nav-fixed-wrap');
        if (navFixedWrap) {
            navFixedWrap.hidden = false;
        }
        
        const navBlurWrap = document.getElementById('ve-nav-blur-wrap');
        if (navBlurWrap) {
            navBlurWrap.hidden = false;
        }

        // Show/hide sidebar element dynamically
        const sidebarEl = document.getElementById('sidebar-nav');
        if (sidebarEl) {
            if (style === 'sidebar') {
                // Show sidebar
                sidebarEl.classList.remove('sidebar-nav--hidden');
                sidebarEl.removeAttribute('aria-hidden');
                // Reinitialize sidebar if needed
                if (typeof initSidebarNav === 'function') {
                    setTimeout(initSidebarNav, 50);
                }
            } else {
                // Hide sidebar
                sidebarEl.classList.add('sidebar-nav--hidden');
                sidebarEl.setAttribute('aria-hidden', 'true');
            }
        }
        
        if (style !== 'sidebar' && typeof NavbarMorphDropdown !== 'undefined') {
            setTimeout(() => {
                window.navbarMorphDropdown = new NavbarMorphDropdown();
            }, 50);
        }

        // When switching to sidebar, ensure sidebar attributes exist
        if (style === 'sidebar') {
            const currentSidebarStyle = this.root.getAttribute('data-sidebar-style') || 'default';
            const currentSidebarPosition = this.root.getAttribute('data-sidebar-position') || 'top';

            this.root.setAttribute('data-sidebar-style', currentSidebarStyle);
            this.root.setAttribute('data-sidebar-position', currentSidebarPosition);

            // Reset collapsed state to avoid visual glitches
            this.root.setAttribute('data-sidebar-collapsed', 'false');
            const sidebar = document.getElementById('sidebar-nav');
            if (sidebar) {
                sidebar.classList.remove('is-collapsed');
            }
            document.cookie = 'sidebar_collapsed=false;path=/;SameSite=Lax';

            // Update position section visibility
            if (this.sidebarPositionSection) {
                this.sidebarPositionSection.hidden = currentSidebarStyle !== 'mini';
            }

            // Update active cards
            this.sidebarStyleCards.forEach(card => {
                card.classList.toggle('active', card.dataset.sidebarStyle === currentSidebarStyle);
            });
            this.sidebarPositionCards.forEach(card => {
                card.classList.toggle('active', card.dataset.sidebarPosition === currentSidebarPosition);
            });
        } else {
            // Hide position section when not sidebar
            if (this.sidebarPositionSection) {
                this.sidebarPositionSection.hidden = true;
            }
        }
    }

    // Sidebar style (default/mini)
    setSidebarStyle(style) {
        this.root.setAttribute('data-sidebar-style', style);
        this.setProperty('--sidebar-style', style);
        this.setThemeAttr('sidebar-style', style);

        // Reset sidebar collapsed state when switching styles to avoid visual glitches
        this.root.setAttribute('data-sidebar-collapsed', 'false');
        const sidebar = document.getElementById('sidebar-nav');
        if (sidebar) {
            sidebar.classList.remove('is-collapsed');
        }
        // Reset cookie for collapsed state
        document.cookie = 'sidebar_collapsed=false;path=/;SameSite=Lax';
        // Re-initialize sidebar nav if it exists
        if (window.sidebarNav) {
            window.sidebarNav.isCollapsed = false;
            window.sidebarNav.updateState?.();
        }

        // Update card states
        this.sidebarStyleCards.forEach(card => {
            card.classList.toggle('active', card.dataset.sidebarStyle === style);
        });

        // Show/hide sidebar mode section (only for default style)
        if (this.sidebarModeSection) {
            this.sidebarModeSection.hidden = style !== 'default';
        }

        // Show/hide sidebar position section (only for mini style)
        if (this.sidebarPositionSection) {
            this.sidebarPositionSection.hidden = style !== 'mini';
        }
    }

    // Sidebar mode (minimal/full) - only for default style
    setSidebarMode(mode) {
        this.root.setAttribute('data-sidebar-mode', mode);
        this.setProperty('--sidebar-mode', mode);
        this.setThemeAttr('sidebar-mode', mode);

        // Update card states
        this.sidebarModeCards.forEach(card => {
            card.classList.toggle('active', card.dataset.sidebarMode === mode);
        });
    }

    // Sidebar position (top/center) - only for mini style
    setSidebarPosition(position) {
        this.root.setAttribute('data-sidebar-position', position);
        this.setProperty('--sidebar-position', position);
        this.setThemeAttr('sidebar-position', position);

        // Update card states
        this.sidebarPositionCards.forEach(card => {
            card.classList.toggle('active', card.dataset.sidebarPosition === position);
        });
    }

    // Sidebar contained layout toggle
    setSidebarContained(contained) {
        const value = contained ? 'true' : 'false';
        this.root.setAttribute('data-sidebar-contained', value);
        this.setProperty('--sidebar-contained', value);
        this.setThemeAttr('sidebar-contained', value);

        if (this.sidebarContainedToggle) {
            this.sidebarContainedToggle.checked = contained;
        }

        // Reset collapsed state to avoid visual glitches
        this.root.setAttribute('data-sidebar-collapsed', 'false');
        const sidebar = document.getElementById('sidebar-nav');
        if (sidebar) {
            sidebar.classList.remove('is-collapsed');
        }
        document.cookie = 'sidebar_collapsed=false;path=/;SameSite=Lax';
        if (window.sidebarNav) {
            window.sidebarNav.isCollapsed = false;
            window.sidebarNav.isContained = contained;
            window.sidebarNav.updateState?.();
        }
    }

    // Navigation fixed toggle
    handleNavFixedToggle(fixed) {
        this.root.setAttribute('data-nav-fixed', fixed ? 'true' : 'false');
        this.setProperty('--nav-fixed', fixed ? 'true' : 'false');
        this.setThemeAttr('nav-fixed', fixed ? 'true' : 'false');
    }
    
    // Navigation blur toggle
    handleNavBlurToggle(blur) {
        this.root.setAttribute('data-nav-blur', blur ? 'true' : 'false');
        this.setProperty('--nav-blur', blur ? 'true' : 'false');
        this.setThemeAttr('nav-blur', blur ? 'true' : 'false');
    }
    
    // Footer type
    setFooterType(type) {
        this.root.setAttribute('data-footer-type', type);
        this.setProperty('--footer-type', type);
        this.setThemeAttr('footer-type', type);
        
        // Update card states
        this.footerTypeCards.forEach(card => {
            card.classList.toggle('active', card.dataset.footerType === type);
        });
    }
    
    // Footer socials toggle
    handleFooterSocialsToggle(show) {
        this.root.setAttribute('data-footer-socials', show ? 'true' : 'false');
        this.setProperty('--footer-socials', show ? 'true' : 'false');
        this.setThemeAttr('footer-socials', show ? 'true' : 'false');
    }
    
    // Footer logo toggle
    handleFooterLogoToggle(show) {
        this.root.setAttribute('data-footer-logo', show ? 'true' : 'false');
        this.setProperty('--footer-logo', show ? 'true' : 'false');
        this.setThemeAttr('footer-logo', show ? 'true' : 'false');
    }

    // Font loading
    loadFont(fontName) {
        if (this.loadedFonts.has(fontName)) return;

        // Manrope is loaded locally, skip CDN
        if (fontName === 'Manrope') {
            this.loadedFonts.add(fontName);
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(fontName)}:wght@400;500;600;700&display=swap`;
        document.head.appendChild(link);
        this.loadedFonts.add(fontName);
    }

    /**
     * Load all fonts and set font-family on card previews
     */
    initFontCardPreviews() {
        if (this._fontCardsInitialized) return;
        this._fontCardsInitialized = true;

        // Batch load all fonts via a single Google Fonts request
        const allFonts = new Set();
        [this.fontCardsContainer, this.headingFontCardsContainer].forEach(container => {
            if (!container) return;
            container.querySelectorAll('.ve__font-card').forEach(card => {
                const font = card.dataset.font;
                if (font && font !== 'inherit' && font !== 'Manrope') {
                    allFonts.add(font);
                }
            });
        });

        if (allFonts.size > 0) {
            const families = Array.from(allFonts).map(f => `family=${encodeURIComponent(f)}:wght@400;600`).join('&');
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = `https://fonts.googleapis.com/css2?${families}&display=swap`;
            document.head.appendChild(link);
            allFonts.forEach(f => this.loadedFonts.add(f));
        }

        // Set font-family on each card preview
        [this.fontCardsContainer, this.headingFontCardsContainer].forEach(container => {
            if (!container) return;
            container.querySelectorAll('.ve__font-card').forEach(card => {
                const font = card.dataset.font;
                const preview = card.querySelector('.ve__font-card-preview');
                if (preview && font && font !== 'inherit') {
                    preview.style.fontFamily = `'${font}', sans-serif`;
                }
            });
        });
    }

    // History - capture from inline styles only (what we've changed)
    captureState() {
        const state = {};
        const inlineVars = [
            '--accent', '--primary', '--secondary', '--background', '--text',
            '--font', '--font-header', '--font-scale',
            '--border1', '--border05',
            '--space-xs', '--space-sm', '--space-md', '--space-lg', '--space-xl',
            '--transition', '--blur-amount', '--max-content-width', '--card-opacity', '--glow-intensity',
            '--shadow-small', '--shadow-medium', '--shadow-large',
            '--gradient-type', '--gradient-angle', '--gradient-pos-x', '--gradient-pos-y', '--gradient-intensity', '--page-gradient',
            '--bg-effect', '--bg-effect-opacity', '--container-width', '--widget-gap',
            '--nav-style', '--sidebar-style', '--sidebar-position', '--sidebar-contained', '--nav-fixed', '--nav-blur', '--nav-socials', '--hover-scale', '--footer-type', '--footer-socials', '--footer-logo',
            '--emoji-pattern', '--emoji-tile-width', '--emoji-tile-height', '--emoji-angle', '--emoji-accent-filter'
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
        state['_gradient-type'] = this.root.getAttribute('data-gradient-type') || 'none';
        state['_gradient-stops'] = JSON.stringify(this.gradientState.stops);
        state['_bg-effect'] = this.root.getAttribute('data-bg-effect') || 'none';
        state['_hover-scale'] = this.root.getAttribute('data-hover-scale') || 'true';
        state['_nav-style'] = this.root.getAttribute('data-nav-style') || 'default';
        state['_sidebar-style'] = this.root.getAttribute('data-sidebar-style') || 'default';
        state['_sidebar-mode'] = this.root.getAttribute('data-sidebar-mode') || 'full';
        state['_sidebar-position'] = this.root.getAttribute('data-sidebar-position') || 'top';
        state['_sidebar-contained'] = this.root.getAttribute('data-sidebar-contained') || 'false';
        state['_nav-fixed'] = this.root.getAttribute('data-nav-fixed') || 'true';
        state['_nav-blur'] = this.root.getAttribute('data-nav-blur') || 'true';
        state['_nav-socials'] = this.root.getAttribute('data-nav-socials') || 'true';
        state['_footer-type'] = this.root.getAttribute('data-footer-type') || 'default';
        state['_footer-socials'] = this.root.getAttribute('data-footer-socials') || 'true';
        state['_footer-logo'] = this.root.getAttribute('data-footer-logo') || 'true';
        state['_emoji-settings'] = JSON.stringify(this.emojiState);
        
        return state;
    }

    applyState(state) {
        // Clear all inline styles first
        const allVars = [
            '--accent', '--primary', '--secondary', '--background', '--text',
            '--font', '--font-header', '--font-scale',
            '--border1', '--border05',
            '--space-xs', '--space-sm', '--space-md', '--space-lg', '--space-xl',
            '--transition', '--blur-amount', '--max-content-width', '--card-opacity', '--glow-intensity',
            '--shadow-small', '--shadow-medium', '--shadow-large',
            '--gradient-type', '--gradient-angle', '--gradient-pos-x', '--gradient-pos-y', '--gradient-intensity', '--page-gradient',
            '--bg-effect', '--bg-effect-opacity', '--container-width',
            '--nav-style', '--sidebar-style', '--sidebar-position', '--sidebar-contained', '--nav-fixed', '--nav-blur', '--nav-socials', '--hover-scale', '--footer-type', '--footer-socials', '--footer-logo',
            '--emoji-pattern', '--emoji-tile-width', '--emoji-tile-height', '--emoji-angle', '--emoji-accent-filter'
        ];
        
        allVars.forEach(key => this.removeProperty(key));
        
        // Apply CSS variables first
        Object.entries(state).forEach(([key, value]) => {
            if (key.startsWith('--') && value) {
                this.setProperty(key, value);
            }
        });
        
        // Load gradient stops BEFORE setting gradient type
        if (state['_gradient-stops']) {
            try {
                this.gradientState.stops = JSON.parse(state['_gradient-stops']);
                this.syncGradientStops();
            } catch (e) {}
        }
        
        // Load emoji settings BEFORE setting background effect
        if (state['_emoji-settings']) {
            try {
                this.emojiState = JSON.parse(state['_emoji-settings']);
                // Update UI without triggering pattern update yet
                this.setEmojiPreset(this.emojiState.preset, false);
                if (this.emojiAngleSlider) {
                    this.emojiAngleSlider.value = this.emojiState.angle;
                    if (this.emojiAngleVal) this.emojiAngleVal.textContent = `${this.emojiState.angle}°`;
                }
                if (this.emojiSizeSlider) {
                    this.emojiSizeSlider.value = this.emojiState.size;
                    if (this.emojiSizeVal) this.emojiSizeVal.textContent = `${this.emojiState.size}px`;
                }
                if (this.emojiSpacingSlider) {
                    this.emojiSpacingSlider.value = this.emojiState.spacing;
                    if (this.emojiSpacingVal) this.emojiSpacingVal.textContent = `${this.emojiState.spacing}px`;
                }
                if (this.emojiAccentToggle) {
                    this.emojiAccentToggle.checked = this.emojiState.useAccent;
                }
                if (this.emojiCustomInput) {
                    this.emojiCustomInput.value = this.emojiState.custom;
                }
                this.syncEmojiSettings();
            } catch (e) {}
        }
        
        // Now apply other state properties
        Object.entries(state).forEach(([key, value]) => {
            // Skip already processed keys
            if (key.startsWith('--') || key === '_gradient-stops' || key === '_emoji-settings') {
                return;
            }
            
            if (key === '_container-width') {
                this.root.setAttribute('data-container-width', value);
                if (this.fullwidthToggle) {
                    this.fullwidthToggle.checked = value === 'fullwidth';
                }
                this.handleFullwidthToggle(value === 'fullwidth');
            } else if (key === '_gradient-type') {
                this.setGradientType(value);
            } else if (key === '_bg-effect') {
                this.setBackgroundEffect(value);
            } else if (key === '_nav-style') {
                this.setNavStyle(value);
            } else if (key === '_sidebar-style') {
                this.setSidebarStyle(value);
            } else if (key === '_sidebar-mode') {
                this.setSidebarMode(value);
            } else if (key === '_sidebar-position') {
                this.setSidebarPosition(value);
            } else if (key === '_sidebar-contained') {
                this.setSidebarContained(value === 'true');
            } else if (key === '_nav-fixed') {
                const isFixed = value === 'true';
                this.root.setAttribute('data-nav-fixed', value);
                this.setThemeAttr('nav-fixed', value);
                if (this.navFixedToggle) {
                    this.navFixedToggle.checked = isFixed;
                }
            } else if (key === '_nav-blur') {
                const hasBlur = value === 'true';
                this.root.setAttribute('data-nav-blur', value);
                this.setThemeAttr('nav-blur', value);
                if (this.navBlurToggle) {
                    this.navBlurToggle.checked = hasBlur;
                }
            } else if (key === '_nav-socials') {
                const show = value === 'true';
                this.root.setAttribute('data-nav-socials', value);
                this.setThemeAttr('nav-socials', value);
                if (this.navSocialsToggle) {
                    this.navSocialsToggle.checked = show;
                }
            } else if (key === '_hover-scale') {
                const hasScale = value === 'true';
                this.root.setAttribute('data-hover-scale', value);
                this.setThemeAttr('hover-scale', value);
                if (this.hoverScaleToggle) {
                    this.hoverScaleToggle.checked = hasScale;
                }
            } else if (key === '_footer-type') {
                this.setFooterType(value);
            } else if (key === '_footer-socials') {
                const show = value === 'true';
                this.root.setAttribute('data-footer-socials', value);
                this.setThemeAttr('footer-socials', value);
                if (this.footerSocialsToggle) {
                    this.footerSocialsToggle.checked = show;
                }
            } else if (key === '_footer-logo') {
                const show = value === 'true';
                this.root.setAttribute('data-footer-logo', value);
                this.setThemeAttr('footer-logo', value);
                if (this.footerLogoToggle) {
                    this.footerLogoToggle.checked = show;
                }
            }
        });
        
        this.loadCurrentValues();
        this.updateGradientPreview();
        
        const bgEffect = this.root.getAttribute('data-bg-effect') || 'none';
        if (bgEffect === 'emoji') {
            this.updateEmojiPattern();
        }
        
        this.updateBorderPreview();
    }

    recordHistory() {
        const state = this.captureState();
        const theme = this.getThemeKey();
        const history = (this.historyByTheme[theme] || []).slice(0, (this.historyIndexByTheme[theme] ?? -1) + 1);
        let index = this.historyIndexByTheme[theme] ?? -1;

        history.push(state);
        if (history.length > this.maxHistory) {
            history.shift();
        } else {
            index++;
        }

        if (index >= history.length) {
            index = history.length - 1;
        }

        this.historyByTheme[theme] = history;
        this.historyIndexByTheme[theme] = index;
        this.updateHistoryButtons();
    }

    undo() {
        const theme = this.getThemeKey();
        if ((this.historyIndexByTheme[theme] ?? -1) > 0) {
            this.historyIndexByTheme[theme]--;
            this.applyState(this.historyByTheme[theme][this.historyIndexByTheme[theme]]);
            this.updateHistoryButtons();
        }
    }

    redo() {
        const theme = this.getThemeKey();
        if ((this.historyIndexByTheme[theme] ?? -1) < (this.historyByTheme[theme]?.length || 0) - 1) {
            this.historyIndexByTheme[theme]++;
            this.applyState(this.historyByTheme[theme][this.historyIndexByTheme[theme]]);
            this.updateHistoryButtons();
        }
    }

    updateHistoryButtons() {
        const theme = this.getThemeKey();
        const index = this.historyIndexByTheme[theme] ?? -1;
        const length = this.historyByTheme[theme]?.length || 0;
        if (this.undoBtn) this.undoBtn.disabled = index <= 0;
        if (this.redoBtn) this.redoBtn.disabled = index >= length - 1;
    }

    reset() {
        const theme = this.getThemeKey();
        const baseline = this.initialStates[theme];

        if (baseline) {
            this.applyState(baseline);
        } else {
            this.root.removeAttribute('style');
            this.loadCurrentValues();
        }

        this.historyByTheme[theme] = [];
        this.historyIndexByTheme[theme] = -1;
        this.recordHistory();
        
        if (typeof notyf !== 'undefined') {
            notyf.success(translate('page-edit.settings_reset') || 'Settings reset');
        }
    }

    /**
     * Apply image preview directly on the live page without uploading.
     * Pass null as objectUrl to revert/clear.
     */
    applyImagePreview(type, objectUrl) {
        if (type === 'bg_image' || type === 'bg_image_light') {
            const theme = type === 'bg_image' ? 'dark' : 'light';
            const styleId = `ve-bg-${theme}-preview`;
            let styleEl = document.getElementById(styleId);
            if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = styleId;
                document.head.appendChild(styleEl);
            }
            if (objectUrl) {
                styleEl.textContent = `html[data-theme="${theme}"] body { background-image: url("${objectUrl}") !important; background-repeat: no-repeat; background-attachment: fixed; background-position: center center; background-size: cover; }`;
            } else {
                styleEl.textContent = `html[data-theme="${theme}"] body { background-image: none !important; }`;
            }
        } else if (type === 'logo' || type === 'logo_light') {
            const isDark = type === 'logo';
            const selector = isDark
                ? '.navbar__logo-dark img, .sidebar-nav__logo-img--dark, .footer__logo-dark img'
                : '.navbar__logo-light img, .sidebar-nav__logo-img--light, .footer__logo-light img';

            document.querySelectorAll(selector).forEach(img => {
                if (objectUrl) {
                    if (!img.dataset.vePrevSrc) img.dataset.vePrevSrc = img.src;
                    img.src = objectUrl;
                } else if (img.dataset.vePrevSrc) {
                    img.src = img.dataset.vePrevSrc;
                    delete img.dataset.vePrevSrc;
                }
            });
        }
    }

    // Save - collect all changed settings
    bindUploadEvents() {
        // File upload inputs — show local preview immediately, defer actual upload to Save
        this.editor.querySelectorAll('input[data-upload]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const type = input.dataset.upload;

                // Revoke previous pending object URL to avoid memory leaks
                if (this.pendingUploads[type]?.objectUrl) {
                    URL.revokeObjectURL(this.pendingUploads[type].objectUrl);
                }

                const objectUrl = URL.createObjectURL(file);

                // Show local preview immediately (no upload yet)
                const previewId = 've-' + type.replace(/_/g, '-') + '-preview';
                const preview = document.getElementById(previewId);
                if (preview) {
                    preview.innerHTML = `<img src="${objectUrl}" alt="" />`;
                }

                // Apply live preview on the actual page
                this.applyImagePreview(type, objectUrl);

                // Store pending upload for save()
                this.pendingUploads[type] = { file, objectUrl };

                // Reveal delete button
                const deleteBtn = this.editor.querySelector(`[data-delete="${type}"]`);
                if (deleteBtn) deleteBtn.hidden = false;

                input.value = '';
            });
        });

        // Delete buttons
        this.editor.querySelectorAll('[data-delete]').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.delete;

                // If pending (not yet uploaded) — remove locally only
                if (this.pendingUploads[type]) {
                    URL.revokeObjectURL(this.pendingUploads[type].objectUrl);
                    delete this.pendingUploads[type];

                    const previewId = 've-' + type.replace(/_/g, '-') + '-preview';
                    const preview = document.getElementById(previewId);
                    if (preview) preview.innerHTML = '';
                    btn.hidden = true;

                    // Revert live page preview
                    this.applyImagePreview(type, null);
                } else {
                    this.deleteSiteImage(type);
                }
            });
        });
    }

    async uploadSiteImage(type, file) {
        const formData = new FormData();
        formData.append('type', type);
        formData.append('image', file);

        try {
            const response = await fetch(u('api/pages/upload-site-image'), {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const previewId = 've-' + type.replace(/_/g, '-') + '-preview';
                const preview = document.getElementById(previewId);
                if (preview) {
                    preview.innerHTML = `<img src="${data.url}" alt="" />`;
                }

                const deleteBtn = this.editor.querySelector(`button[data-delete="${type}"]`);
                if (deleteBtn) deleteBtn.hidden = false;
            } else {
                if (typeof notyf !== 'undefined') notyf.error(data.error || translate('page-edit.upload_error'));
            }
        } catch (error) {
            console.error('Upload error:', error);
            if (typeof notyf !== 'undefined') notyf.error(translate('page-edit.upload_error'));
        }
    }

    async deleteSiteImage(type) {
        try {
            const response = await fetch(u('api/pages/delete-site-image'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ type })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const previewId = 've-' + type.replace(/_/g, '-') + '-preview';
                const preview = document.getElementById(previewId);
                if (preview) preview.innerHTML = '';

                const deleteBtn = this.editor.querySelector(`button[data-delete="${type}"]`);
                if (deleteBtn) deleteBtn.hidden = true;

                this.applyImagePreview(type, null);
            } else {
                if (typeof notyf !== 'undefined') notyf.error(data.error || 'Error');
            }
        } catch (error) {
            console.error('Delete error:', error);
        }
    }

    _buildSavePayload(state) {
        const colors = {};
        const settings = {};

        ['--accent', '--primary', '--secondary', '--background', '--text'].forEach(key => {
            if (state[key]) colors[key] = state[key];
            [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach(step => {
                const shadeKey = `${key}-${step}`;
                if (state[shadeKey]) colors[shadeKey] = state[shadeKey];
            });
        });

        if (state['--border1']) {
            colors['--border1'] = parseFloat(state['--border1']);
        }

        colors['--gradient-type'] = state['_gradient-type'] || 'none';
        colors['--gradient-stops'] = state['_gradient-stops'] || JSON.stringify(this.gradientState.stops);
        ['--gradient-angle', '--gradient-pos-x', '--gradient-pos-y', '--gradient-intensity'].forEach(key => {
            if (state[key]) colors[key] = state[key];
        });

        colors['--bg-effect'] = state['_bg-effect'] || 'none';
        if (state['--bg-effect-opacity']) {
            colors['--bg-effect-opacity'] = state['--bg-effect-opacity'];
        }

        colors['--emoji-settings'] = state['_emoji-settings'] || JSON.stringify(this.emojiState);

        colors['--container-width'] = state['_container-width'] || 'container';

        colors['--nav-style'] = state['_nav-style'] || 'default';
        colors['--sidebar-style'] = state['_sidebar-style'] || 'default';
        colors['--sidebar-mode'] = state['_sidebar-mode'] || 'full';
        colors['--sidebar-position'] = state['_sidebar-position'] || 'top';
        colors['--sidebar-contained'] = state['_sidebar-contained'] || 'false';
        colors['--nav-fixed'] = state['_nav-fixed'] || 'true';
        colors['--nav-blur'] = state['_nav-blur'] || 'true';
        colors['--nav-socials'] = state['_nav-socials'] || 'true';
        colors['--hover-scale'] = state['_hover-scale'] || 'true';
        colors['--footer-type'] = state['_footer-type'] || 'default';
        colors['--footer-socials'] = state['_footer-socials'] || 'true';
        colors['--footer-logo'] = state['_footer-logo'] || 'true';

        const settingsVarsMap = {
            '--font': 'font',
            '--font-header': 'font_header',
            '--font-scale': 'font_scale',
            '--space-xs': 'space_xs',
            '--space-sm': 'space_sm',
            '--space-md': 'space_md',
            '--space-lg': 'space_lg',
            '--space-xl': 'space_xl',
            '--transition': 'transition',
            '--blur-amount': 'blur_amount',
            '--card-opacity': 'card_opacity',
            '--glow-intensity': 'glow_intensity',
            '--max-content-width': 'max_content_width',
            '--widget-gap': 'widget_gap',
            '--shadow-small': 'shadow_small',
            '--shadow-medium': 'shadow_medium',
            '--shadow-large': 'shadow_large'
        };

        Object.entries(settingsVarsMap).forEach(([cssKey, settingKey]) => {
            if (state[cssKey]) {
                settings[settingKey] = state[cssKey];
            }
        });

        return { colors, settings };
    }

    _generateShadesForTheme(variable, baseColor, isLight) {
        const shades = {};
        const hsl = tinycolor(baseColor).toHsl();
        const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
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
            shades[`${variable}-${step}`] = shade;
        });
        return shades;
    }

    _adaptColorsForOppositeTheme(colors, fromTheme) {
        const adapted = { ...colors };
        const toLight = fromTheme === 'dark';
        const baseVars = ['--accent', '--primary', '--secondary', '--background', '--text'];

        baseVars.forEach(variable => {
            if (!colors[variable]) return;
            const hsl = tinycolor(colors[variable]).toHsl();

            if (variable === '--accent') {
                if (toLight && hsl.l > 0.6) {
                    hsl.l = Math.max(0.35, hsl.l * 0.55);
                    hsl.s = Math.min(1, hsl.s * 0.85);
                } else if (!toLight && hsl.l < 0.5) {
                    hsl.l = Math.min(0.7, hsl.l * 1.6 + 0.15);
                }
            } else {
                hsl.l = 1 - hsl.l;
                if (variable === '--background') {
                    hsl.l = toLight ? Math.max(hsl.l, 0.97) : Math.max(Math.min(hsl.l, 0.08), 0.04);
                } else if (variable === '--secondary') {
                    hsl.l = toLight ? Math.max(hsl.l, 0.93) : Math.max(Math.min(hsl.l, 0.15), 0.08);
                }
            }

            adapted[variable] = tinycolor(hsl).toHexString();
            Object.assign(adapted, this._generateShadesForTheme(variable, adapted[variable], toLight));
        });

        return adapted;
    }

    _isThemeChangeEnabled() {
        return document.querySelector('meta[name="change-theme"]')?.getAttribute('content') === 'true';
    }

    async _showApplyOppositeThemeConfirm(oppositeTheme) {
        const themeLabel = await asyncTranslate(`page-edit.theme_${oppositeTheme}`);
        const replaceMap = { ':theme': themeLabel };

        const [message, title, confirmText, cancelText] = await Promise.all([
            asyncTranslate('page-edit.apply_opposite_theme_message', replaceMap),
            asyncTranslate('page-edit.apply_opposite_theme_title', replaceMap),
            asyncTranslate('page-edit.apply_opposite_theme_confirm'),
            asyncTranslate('page-edit.apply_opposite_theme_cancel'),
        ]);

        return new Promise((resolve) => {
            if (typeof app !== 'undefined' && app.confirmations) {
                app.confirmations.showConfirmDialog({
                    message,
                    title,
                    confirmText,
                    cancelText,
                    type: 'info',
                    onConfirm: () => resolve(true),
                    onCancel: () => resolve(false),
                });
            } else {
                resolve(confirm(message));
            }
        });
    }

    async _sendThemeSave(colors, settings, theme) {
        const response = await fetch(u('api/pages/save-theme'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ colors, settings, theme })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Save failed');
        return data;
    }

    async save() {
        const theme = this.getThemeKey();
        const state = this.captureState();
        const { colors, settings } = this._buildSavePayload(state);

        this.saveBtn.disabled = true;
        this.saveBtn.classList.add('saving');

        if (Object.keys(this.pendingUploads).length > 0) {
            for (const [type, { file, objectUrl }] of Object.entries(this.pendingUploads)) {
                await this.uploadSiteImage(type, file);
                URL.revokeObjectURL(objectUrl);
            }
            this.pendingUploads = {};
        }

        try {
            await this._sendThemeSave(colors, settings, theme);

            if (this._isThemeChangeEnabled()) {
                const oppositeTheme = theme === 'dark' ? 'light' : 'dark';
                const applyToOpposite = await this._showApplyOppositeThemeConfirm(oppositeTheme);

                if (applyToOpposite) {
                    const adaptedColors = this._adaptColorsForOppositeTheme(colors, theme);
                    await this._sendThemeSave(adaptedColors, settings, oppositeTheme);
                }
            }

            this.close();
            if (typeof notyf !== 'undefined') notyf.success(translate('page-edit.settings_saved') || 'Saved');
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
