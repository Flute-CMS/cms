$(function () {
    const createNavSortableInstances = () => {
        const nestedSortables = document.querySelectorAll(
            '.nav-nested-sortable',
        );
        return Array.from(nestedSortables).map(
            (nestedSortable) =>
                new Sortable(nestedSortable, {
                    group: 'nested',
                    handle: '.sortable-handle',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onMove: (event) => {
                        const level = $(event.to).parents(
                            '.nav-nested-sortable',
                        ).length;
                        const length = $(event.dragged).find(
                            '.nav-nested-sortable > li',
                        ).length;
                        return !((length > 0 && level > 0) || level > 1);
                    },
                }),
        );
    };

    let sortablesNav = createNavSortableInstances();

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', () => {
            if ($('#saveNavigation').length)
                sortablesNav = createNavSortableInstances();
        });

    $(document).on('click', '#saveNavigation', () => {
        const orderedIds = getNestedOrder(sortablesNav);
        saveNavigationOrder(orderedIds);
    });

    function getNestedOrder(sortablesNav) {
        let order = [];
        sortablesNav.forEach((sortable) => {
            let items = sortable.toArray();
            items.forEach((itemId, index) => {
                let element = document.getElementById(itemId);
                let parentSortable = element.closest('.nav-nested-sortable');
                let parentId = parentSortable
                    ? parentSortable.getAttribute('id')
                    : null;
                order.push({
                    id: itemId.replace('nav-', ''),
                    parentId: parentId
                        ? parentId.replace('nav-', '')
                        : parentId,
                    position: index,
                });
            });
        });
        return order;
    }

    function saveNavigationOrder(order) {
        let data = order.map((item) => ({
            id: item.id,
            parent_id: item.parentId,
            position: item.position + 1,
        }));

        fetch(u('admin/api/navigation/save-order'), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': document
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

    $(document).on('click', '.navigation-group .delete', async function () {
        let itemId = $(this).data('deleteitem');
        if (await asyncConfirm(translate('admin.navigation.confirm_delete'))) {
            sendRequest({}, 'admin/api/navigation/' + itemId, 'DELETE');
        }
    });
});
