document.addEventListener('DOMContentLoaded', () => {
    initCustomization();

    const containerWidth = getCookie('container-width') || 'normal';
    applyContainerWidth(containerWidth);
});

function initCustomization() {
    const themeButtons = document.querySelectorAll('.theme-toggle__btn');
    themeButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const theme = this.getAttribute('data-theme');
            setTheme(theme);

            themeButtons.forEach((btn) => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const colorSchemeButtons = document.querySelectorAll('.color-scheme__item');
    colorSchemeButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const scheme = this.getAttribute('data-color-scheme');
            setColorScheme(scheme);

            colorSchemeButtons.forEach((btn) => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const containerWidthButtons = document.querySelectorAll(
        '.container-width__btn',
    );
    containerWidthButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const width = this.getAttribute('data-container-width');
            setContainerWidth(width);

            containerWidthButtons.forEach((btn) =>
                btn.classList.remove('active'),
            );
            this.classList.add('active');
        });
    });

    const customizationButton = document.querySelector(
        '.navbar__customization',
    );
    if (customizationButton) {
        customizationButton.addEventListener('click', function () {
            openCustomizationSidebar();
        });
    }
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);

    setCookie('theme', theme, 365);

    updateColorSchemePreviewColors(theme);
}

function setColorScheme(scheme) {
    setCookie('color-scheme', scheme, 365);

    applyColorScheme(scheme);
}

function setContainerWidth(width) {
    setCookie('container-width', width, 365);

    applyContainerWidth(width);
}

function applyColorScheme(scheme) {
    document.documentElement.setAttribute('data-color-scheme', scheme);
}

function applyContainerWidth(width) {
    const containers = document.querySelectorAll('.container');
    containers.forEach((container) => {
        if (width === 'wide') {
            container.classList.add('container-wide');
        } else {
            container.classList.remove('container-wide');
        }
    });
}

function updateColorSchemePreviewColors(theme) {
    const colorSchemeItems = document.querySelectorAll('.color-scheme__item');

    colorSchemeItems.forEach((item) => {
        const scheme = item.getAttribute('data-color-scheme');
        const primaryPreview = item.querySelector('.color-scheme__primary');
        const accentPreview = item.querySelector('.color-scheme__accent');

        if (primaryPreview && accentPreview) {
            const colorMap = {
                default: {
                    light: {
                        primary: '#0d0d0d',
                        accent: '#76bd50',
                    },
                    dark: {
                        primary: '#F1F1F1',
                        accent: '#A5FF75',
                    },
                },
                blue: {
                    light: {
                        primary: '#0A3880',
                        accent: '#4285F4',
                    },
                    dark: {
                        primary: '#1565C0',
                        accent: '#5E97F6',
                    },
                },
                purple: {
                    light: {
                        primary: '#4A148C',
                        accent: '#9C27B0',
                    },
                    dark: {
                        primary: '#6A1B9A',
                        accent: '#CE93D8',
                    },
                },
                orange: {
                    light: {
                        primary: '#E65100',
                        accent: '#FF9800',
                    },
                    dark: {
                        primary: '#EF6C00',
                        accent: '#FFAB40',
                    },
                },
                red: {
                    light: {
                        primary: '#B71C1C',
                        accent: '#F44336',
                    },
                    dark: {
                        primary: '#C62828',
                        accent: '#EF5350',
                    },
                },
            };

            if (colorMap[scheme] && colorMap[scheme][theme]) {
                primaryPreview.style.backgroundColor = colorMap[scheme][theme].primary;
                accentPreview.style.backgroundColor = colorMap[scheme][theme].accent;
            }
        }
    });
}

function openCustomizationSidebar() {
    const sidebar = document.getElementById('customization-modal');
    if (sidebar && sidebar.dialogInstance) {
        sidebar.dialogInstance.show();
    }
}

// Helper function to set cookies
function setCookie(name, value, days) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + (value || '') + expires + '; path=/';
}

// Helper function to get cookies
function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

document.body.addEventListener('htmx:afterRequest', (event) => {
    const target = event.detail.target;

    if (target && target.hasAttribute('data-theme')) {
        const theme = target.getAttribute('data-theme');
        setTheme(theme);
    }

    if (target && target.hasAttribute('data-color-scheme')) {
        const scheme = target.getAttribute('data-color-scheme');
    }

    if (target && target.hasAttribute('data-container-width')) {
        const width = target.getAttribute('data-container-width');
    }
});
