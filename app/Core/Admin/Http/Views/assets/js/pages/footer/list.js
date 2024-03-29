document.addEventListener('DOMContentLoaded', function () {
    var nestedSortables = [].slice.call(
        document.querySelectorAll('.nested-sortable'),
    );

    // Создаем экземпляры Sortable для каждого вложенного списка
    var sortables = nestedSortables.map(function (nestedSortable) {
        return new Sortable(nestedSortable, {
            group: 'nested',
            handle: '.sortable-handle',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onMove(event) {
                const lvl = $(event.to).parents('.nested-sortable').length;
                const length = $(event.dragged).find(
                    '.nested-sortable > li',
                ).length;

                if ((length > 0 && lvl > 0) || lvl > 1) {
                    return false;
                }
            },
        });
    });

    $('#save').on('click', (e) => {
        let orderedIds = getNestedOrder(sortables);
        saveRoleOrder(orderedIds);
    });

    function getNestedOrder(sortables) {
        let order = [];
        sortables.forEach((sortable) => {
            let items = sortable.toArray();
            items.forEach((itemId, index) => {
                let element = document.getElementById(itemId);
                let parentSortable = element.closest('.nested-sortable');
                let parentId = parentSortable ? parentSortable.getAttribute('id') : null;
                order.push({ id: itemId, parentId: parentId, position: index });
            });
        });
        return order;
    }

    function saveRoleOrder(order) {
        let data = order.map((item) => ({
            id: item.id,
            parent_id: item.parentId,
            position: item.position + 1,
        }));

        fetch(u('admin/api/footer/save-order'), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                "x-csrf-token":document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            },
            body: JSON.stringify({ order: data }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.error) throw new Error(data.error);

                toast({
                    type: 'success',
                    message: data.success ?? translate('def.success'),
                });
            })
            .catch((error) => {
                toast({
                    type: 'error',
                    message: error ?? translate('def.unknown_error'),
                });
            });
    }

    function ajaxModuleAction(url, method, data = {}) {
        $.ajax({
            url: url,
            type: method,
            data: {
                ...data,
                ...{
                    "x-csrf-token":csrfToken,
                },
            },
            success: function (response) {
                toast({
                    type: 'success',
                    message: response.success ?? translate('def.success'),
                });

                setTimeout(() => window.location.reload(), 1000);
            },
            error: function (xhr, status, error) {
                toast({
                    type: 'error',
                    message:
                        xhr?.responseJSON?.error ??
                        translate('def.unknown_error'),
                });
            },
        });
    }

    $(document).on('click', '.delete', function () {
        let itemId = $(this).data('deleteitem');
        if (confirm(translate('admin.footer.confirm_delete'))) {
            ajaxModuleAction(u('admin/api/footer/' + itemId), 'DELETE');
            // Удаление элемента из DOM
            $(this).closest('.draggable').remove();
        }
    });
});
