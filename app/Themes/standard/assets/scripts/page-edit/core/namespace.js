/**
 * FlutePageEdit namespace
 * Central namespace for page editor modules
 */
window.FlutePageEdit = window.FlutePageEdit || {
    version: '2.0.0',
    modules: {},

    /**
     * Register a module
     * @param {string} name - Module name
     * @param {object} module - Module object
     */
    register(name, module) {
        this.modules[name] = module;
    },

    /**
     * Get a registered module
     * @param {string} name - Module name
     * @returns {object|null}
     */
    get(name) {
        return this.modules[name] || null;
    },

    /**
     * Check if module is registered
     * @param {string} name - Module name
     * @returns {boolean}
     */
    has(name) {
        return name in this.modules;
    }
};

/**
 * Utility functions
 */
window.FlutePageEdit.utils = {
    /**
     * Get CSRF token from meta tag or cookie
     * @returns {string}
     */
    getCsrfToken() {
        try {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
            if (m) {
                try { return decodeURIComponent(m[1]); } catch { return m[1]; }
            }
        } catch {}
        return '';
    },

    /**
     * Fetch with CSRF token
     * @param {string} url - URL to fetch
     * @param {object} options - Fetch options
     * @returns {Promise<Response>}
     */
    csrfFetch(url, options = {}) {
        const headers = Object.assign({}, options.headers || {});
        const token = this.getCsrfToken();
        if (token && !('X-CSRF-Token' in headers)) {
            headers['X-CSRF-Token'] = token;
        }
        return fetch(url, { ...options, headers });
    },

    /**
     * Get current page path
     * @returns {string}
     */
    getCurrentPath() {
        return window.location.pathname || '/';
    },

    /**
     * Debounce function
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in ms
     * @returns {Function}
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     * @param {Function} func - Function to throttle
     * @param {number} limit - Time limit in ms
     * @returns {Function}
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Escape HTML attribute value
     * @param {*} val - Value to escape
     * @returns {string}
     */
    escapeAttr(val) {
        return String(val ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    },

    /**
     * Create skeleton loader HTML
     * @returns {string}
     */
    createSkeleton() {
        return `<div class="skeleton page-edit-skeleton"
             style="animation: skeleton-loading 1.5s infinite ease-in-out;">
        </div>`;
    },

    /**
     * Log error with context
     * @param {string} context - Error context
     * @param {Error|string} error - Error object or message
     */
    logError(context, error) {
        console.error(`PageEditor [${context}]:`, error);
    }
};

// AbortSignal.timeout polyfill
if (!AbortSignal.timeout) {
    AbortSignal.timeout = function timeout(ms) {
        const controller = new AbortController();
        setTimeout(
            () => controller.abort(new DOMException('TimeoutError')),
            ms
        );
        return controller.signal;
    };
}
