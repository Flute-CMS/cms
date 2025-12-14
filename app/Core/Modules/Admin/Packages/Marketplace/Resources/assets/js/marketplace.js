/* Marketplace JavaScript */
(function () {
    const doc = document;

    function ready(fn) {
        if (doc.readyState !== 'loading') return fn();
        doc.addEventListener('DOMContentLoaded', fn);
    }

    function init() {
        initCardNavigation();
        initLazyLoading();
        initSegmentedControls();
        initSorting();
        initViewToggle();
    }

    // Card click navigation
    function initCardNavigation() {
        doc.querySelectorAll('.mp-card').forEach((card) => {
            const titleLink = card.querySelector('.title');
            const coverLink = card.querySelector('.cover');
            
            if (!titleLink && !coverLink) return;
            
            card.style.cursor = 'pointer';
            card.addEventListener('click', (e) => {
                // Don't navigate if clicking on interactive elements
                if (e.target.closest('button, a, select, input')) return;
                
                const href = titleLink?.getAttribute('href') || coverLink?.getAttribute('href');
                if (href) {
                    // Use htmx if available for smooth navigation
                    if (window.htmx && !e.ctrlKey && !e.metaKey) {
                        htmx.ajax('GET', href, { target: '#main', swap: 'innerHTML' });
                    } else {
                        window.location.href = href;
                    }
                }
            });
        });
    }

    // Lazy load images with IntersectionObserver
    function initLazyLoading() {
        const lazyImages = doc.querySelectorAll('.mp-card .cover img, .mp-product-cover img, .mp-gallery-item img');
        
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.getAttribute('data-src') || img.getAttribute('src');
                        if (src && !img.src) {
                            img.src = src;
                        }
                        observer.unobserve(img);
                    }
                });
            }, { rootMargin: '200px' });
            
            lazyImages.forEach((img) => observer.observe(img));
        }
    }

    function initSegmentedControls() {
        doc.querySelectorAll('.segment .seg').forEach((label) => {
            label.addEventListener('mouseenter', () => {
                label.style.opacity = '0.8';
            });
            label.addEventListener('mouseleave', () => {
                label.style.opacity = '';
            });
        });
    }

    function initSorting() {
        const grid = doc.querySelector('[data-grid]');
        const sortSelect = doc.querySelector('.sort-control');
        
        function sortElements(parent, selector, mode) {
            if (!parent) return;
            
            const items = Array.from(parent.querySelectorAll(selector));
            const collator = new Intl.Collator(undefined, { sensitivity: 'base' });
            
            items.sort((a, b) => {
                const aDownloads = parseInt(a.getAttribute('data-downloads') || '0', 10);
                const bDownloads = parseInt(b.getAttribute('data-downloads') || '0', 10);
                const aName = a.getAttribute('data-name') || '';
                const bName = b.getAttribute('data-name') || '';
                const aPaid = parseInt(a.getAttribute('data-paid') || '0', 10);
                const bPaid = parseInt(b.getAttribute('data-paid') || '0', 10);
                
                switch (mode) {
                    case 'downloads':
                        return bDownloads - aDownloads;
                    case 'name':
                        return collator.compare(aName, bName);
                    case 'featured':
                    default:
                        // Paid first, then by downloads
                        if (aPaid !== bPaid) return bPaid - aPaid;
                        return bDownloads - aDownloads;
                }
            });
            
            items.forEach((el) => parent.appendChild(el));
        }
        
        function applySort() {
            const mode = sortSelect ? sortSelect.value : 'featured';
            sortElements(grid, '.mp-card:not(.skeleton)', mode);
        }
        
        if (sortSelect) {
            sortSelect.addEventListener('change', applySort);
        }
        
        applySort();
    }

    // View toggle (grid/list) with localStorage persistence
    function initViewToggle() {
        const root = doc.querySelector('.admin-marketplace');
        const viewBtns = doc.querySelectorAll('.view-toggle .seg');
        
        if (!root || !viewBtns.length) return;
        
        function setView(mode) {
            root.classList.remove('view-grid', 'view-list');
            root.classList.add(mode === 'list' ? 'view-list' : 'view-grid');
            localStorage.setItem('mp_view', mode);
            
            viewBtns.forEach((btn) => {
                btn.classList.toggle('active', btn.getAttribute('data-view') === mode);
            });
        }
        
        const savedView = localStorage.getItem('mp_view') || 'grid';
        setView(savedView);
        
        viewBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                setView(btn.getAttribute('data-view') || 'grid');
            });
        });
    }

    // Initialize on page load
    ready(init);

    // Reinitialize after htmx swaps
    doc.addEventListener('htmx:afterSwap', init);
    doc.addEventListener('htmx:afterSettle', init);

    // Expose init for manual re-initialization
    window.MarketplaceInit = init;
})();
