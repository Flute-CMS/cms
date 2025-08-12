/* Minimal interactivity for Marketplace */
(function () {
  const doc = document;

  function ready(fn) {
    if (doc.readyState !== 'loading') return fn();
    doc.addEventListener('DOMContentLoaded', fn);
  }

  ready(() => {
    // Legacy list row navigation (keep)
    doc.querySelectorAll('.marketplace-row').forEach((row) => {
      const link = row.querySelector('.marketplace-cell.title a');
      if (!link) return;
      row.style.cursor = 'pointer';
      row.addEventListener('click', (e) => {
        const isButton = e.target.closest('button, a');
        if (isButton) return;
        window.location.href = link.href;
      });
    });

    // Card click navigation
    doc.querySelectorAll('.mp-card').forEach((card) => {
      const link = card.querySelector('.title') || card.querySelector('.cover');
      if (!link) return;
      card.style.cursor = 'pointer';
      card.addEventListener('click', (e) => {
        const isInteractive = e.target.closest('button, a, select, input');
        if (isInteractive) return;
        const href = (card.querySelector('.title') || card.querySelector('.cover'))?.getAttribute('href');
        if (href) window.location.href = href;
      });
    });

    // Preserve filters in localStorage
    const filterFields = ['searchQuery', 'selectedCategory', 'priceFilter', 'statusFilter'];
    filterFields.forEach((name) => {
      const el = doc.querySelector(`[name="${name}"]`);
      if (!el) return;
      const key = `mp_${name}`;
      // restore
      const saved = localStorage.getItem(key);
      if (saved !== null && el.value !== saved) el.value = saved;
      // save on change/input
      const handler = () => localStorage.setItem(key, el.value);
      el.addEventListener('change', handler);
      el.addEventListener('input', handler);
    });

    // Lazy load images
    const lazyImages = doc.querySelectorAll('.module-thumb img, .mp-card .cover img, .details-header .cover img');
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = entry.target;
            const src = img.getAttribute('data-src') || img.getAttribute('src');
            if (src) img.src = src;
            obs.unobserve(img);
          }
        });
      }, { rootMargin: '200px' });
      lazyImages.forEach((img) => io.observe(img));
    }

    // Subtle hover feedback
    doc.querySelectorAll('.marketplace-row').forEach((row) => {
      row.addEventListener('mouseenter', () => row.classList.add('hovered'));
      row.addEventListener('mouseleave', () => row.classList.remove('hovered'));
    });

    // Segmented status control -> updates hidden select[name=statusFilter]
    doc.querySelectorAll('.segment .seg').forEach((btn) => {
      btn.addEventListener('click', () => {
        const value = btn.getAttribute('data-value') || '';
        const select = doc.querySelector('select[name="statusFilter"]');
        if (!select) return;
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        // UI state
        const parent = btn.closest('.segment');
        parent.querySelectorAll('.seg').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });

    // Price and Category chips -> update hidden selects
    doc.querySelectorAll('.chips-row .chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        const type = chip.getAttribute('data-chip');
        const value = chip.getAttribute('data-value') || '';
        let selectName = null;
        if (type === 'price') selectName = 'priceFilter';
        else if (type === 'category') selectName = 'selectedCategory';
        if (!selectName) return;
        const select = doc.querySelector(`select[name="${selectName}"]`);
        if (!select) return;
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        // UI
        const row = chip.closest('.chips-row');
        row.querySelectorAll('.chip').forEach((c) => c.classList.remove('active'));
        chip.classList.add('active');
      });
    });

    // Client-side sorting for both views
    const grid = doc.querySelector('[data-grid]');
    const rows = doc.querySelector('[data-rows]');
    const sortSel = doc.querySelector('.sort-control');
    function sortElements(parent, selector, mode) {
      if (!parent) return;
      const items = Array.from(parent.querySelectorAll(selector));
      const collator = new Intl.Collator(undefined, { sensitivity: 'base' });
      items.sort((a, b) => {
        const ad = parseInt(a.getAttribute('data-downloads') || '0', 10);
        const bd = parseInt(b.getAttribute('data-downloads') || '0', 10);
        const an = a.getAttribute('data-name') || '';
        const bn = b.getAttribute('data-name') || '';
        if (mode === 'downloads' || mode === 'featured') return bd - ad;
        if (mode === 'name') return collator.compare(an, bn);
        return 0;
      });
      items.forEach((el) => parent.appendChild(el));
    }
    function applySort() {
      const mode = sortSel ? sortSel.value : 'featured';
      sortElements(grid, '.mp-card:not(.skeleton)', mode);
      sortElements(rows, '.mp-row', mode);
    }
    if (sortSel) sortSel.addEventListener('change', applySort);
    applySort();

    // View toggle (grid/list) with persistence
    const root = doc.querySelector('.admin-marketplace');
    const viewBtns = doc.querySelectorAll('.view-toggle .seg');
    function setView(mode) {
      if (!root) return;
      root.classList.remove('view-grid', 'view-list');
      root.classList.add(mode === 'list' ? 'view-list' : 'view-grid');
      localStorage.setItem('mp_view', mode);
      viewBtns.forEach((b) => b.classList.toggle('active', b.getAttribute('data-view') === mode));
    }
    const savedView = localStorage.getItem('mp_view') || 'grid';
    setView(savedView);
    viewBtns.forEach((btn) => btn.addEventListener('click', () => setView(btn.getAttribute('data-view') || 'grid')));
  });
})();
