$(document).ready(function () {
    var sortMode = false;
    var sortableInstances = [];
    var sectionSortable = null;
    var hasChanges = false;

    function insertToggleButton() {
        if ($('.sidebar__sort-toggle').length) return;

        var $btn = $('<button/>', {
            class: 'sidebar__sort-toggle',
            'data-tooltip': 'Reorder menu',
            'data-tooltip-placement': 'right',
        }).html(
            '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256">' +
            '<path d="M104,60A12,12,0,1,1,92,48,12,12,0,0,1,104,60Zm60,12a12,12,0,1,0-12-12A12,12,0,0,0,164,72Z' +
            'M92,116a12,12,0,1,0,12,12A12,12,0,0,0,92,116Zm72,0a12,12,0,1,0,12,12A12,12,0,0,0,164,116Z' +
            'M92,184a12,12,0,1,0,12,12A12,12,0,0,0,92,184Zm72,0a12,12,0,1,0,12,12A12,12,0,0,0,164,184Z"/>' +
            '</svg>'
        );

        $btn.insertBefore('.sidebar__toggle');
    }

    insertToggleButton();

    $(document).on('click', '.sidebar__sort-toggle', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if ($('.sidebar').hasClass('collapsed')) return;
        toggleSortMode();
    });

    function toggleSortMode() {
        sortMode = !sortMode;
        var $sidebar = $('.sidebar');
        var $btn = $('.sidebar__sort-toggle');

        if (sortMode) {
            hasChanges = false;
            $sidebar.addClass('sidebar--sorting');
            $btn.addClass('active');
            enableSortable();
        } else {
            $sidebar.removeClass('sidebar--sorting');
            $btn.removeClass('active');
            disableSortable();
            if (hasChanges) saveSidebarOrder();
        }
    }

    function enableSortable() {
        if (typeof Sortable === 'undefined') return;

        sortableInstances = [];

        $('.sidebar__menu-list[data-sidebar-sortable]').each(function () {
            var instance = Sortable.create(this, {
                group: 'sidebar-items',
                animation: 300,
                easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                ghostClass: 'sidebar-sort-ghost',
                chosenClass: 'sidebar-sort-chosen',
                dragClass: 'sidebar-sort-drag',
                handle: '.sidebar__menu-item',
                filter: '.menu-sub, .menu-sub *',
                preventOnFilter: false,
                direction: 'vertical',
                fallbackOnBody: true,
                swapThreshold: 0.55,
                fallbackTolerance: 3,
                forceFallback: true,
                onStart: function () {
                    document.body.classList.add('sidebar-dragging');
                },
                onEnd: function () {
                    document.body.classList.remove('sidebar-dragging');
                    hasChanges = true;
                },
            });
            sortableInstances.push(instance);
        });

        var contentEl = document.querySelector('.sidebar__content');
        if (contentEl) {
            sectionSortable = Sortable.create(contentEl, {
                group: 'sidebar-sections',
                animation: 300,
                easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
                ghostClass: 'sidebar-section-ghost',
                chosenClass: 'sidebar-section-chosen',
                dragClass: 'sidebar-section-drag',
                handle: '.sidebar__section-toggle',
                direction: 'vertical',
                fallbackOnBody: true,
                swapThreshold: 0.55,
                forceFallback: true,
                onStart: function () {
                    document.body.classList.add('sidebar-dragging');
                },
                onEnd: function () {
                    document.body.classList.remove('sidebar-dragging');
                    hasChanges = true;
                },
            });
        }
    }

    function disableSortable() {
        sortableInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        sortableInstances = [];

        if (sectionSortable && sectionSortable.destroy) {
            sectionSortable.destroy();
            sectionSortable = null;
        }
    }

    function serializeSidebarOrder() {
        var order = [];

        $('.sidebar__section').each(function () {
            var $section = $(this);
            var sectionKey = $section.attr('data-section-key') || '';
            var items = [];

            $section.find('.sidebar__menu-list > .sidebar__menu-item').each(function () {
                var key = $(this).attr('data-item-key');
                if (key) items.push(key);
            });

            order.push({ section: sectionKey, items: items });
        });

        return order;
    }

    function saveSidebarOrder() {
        var order = serializeSidebarOrder();
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        fetch(u('admin/api/sidebar/order'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify({ order: order }),
        }).catch(function () {});
    }

    $(document).on('htmx:afterSwap', function () {
        if (sortMode) {
            sortMode = false;
            $('.sidebar').removeClass('sidebar--sorting');
            $('.sidebar__sort-toggle').removeClass('active');
            disableSortable();
        }
        insertToggleButton();
    });

    window.addEventListener('sidebar-refresh', function () {
        if (sortMode) {
            sortMode = false;
            $('.sidebar').removeClass('sidebar--sorting');
            $('.sidebar__sort-toggle').removeClass('active');
            disableSortable();
        }
        setTimeout(insertToggleButton, 100);
    });
});
