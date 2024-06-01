$(function () {
    const createSortableInstances = () => {
        const nestedSortables = document.querySelectorAll('.footer-nested-sortable');
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
                            '.footer-nested-sortable',
                        ).length;
                        const length = $(event.dragged).find(
                            '.footer-nested-sortable > li',
                        ).length;
                        return !((length > 0 && level > 0) || level > 1);
                    },
                }),
        );
    };

    let sortablesFooter = createSortableInstances();

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', () => {
            sortablesFooter = createSortableInstances();
        });

    $(document).on('click', '#saveFooter', () => {
        const orderedIds = sortablesFooter
            .map((sortable) =>
                sortable.toArray().map((itemId, index) => ({
                    id: itemId.replace('fot-', ''),
                    parentId: $(`#${itemId}`)
                        .closest('.footer-nested-sortable')
                        .attr('id')
                        ?.replace('fot-', ''),
                    position: index,
                })),
            )
            .flat();

        saveRoleOrder(orderedIds);
    });

    function saveRoleOrder(order) {
        fetch(u('admin/api/footer/save-order'), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': $('meta[name="csrf-token"]').attr('content'),
            },
            body: JSON.stringify({
                order: order.map((item) => ({
                    ...item,
                    position: item.position + 1,
                })),
            }),
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
                    message: error.message ?? translate('def.unknown_error'),
                });
            });
    }

    $(document).on('click', '.footer-group .delete', async function () {
        const itemId = $(this).data('deleteitem');
        if (await asyncConfirm(translate('admin.footer.confirm_delete'))) {
            sendRequest({}, 'admin/api/footer/' + itemId, 'DELETE');
        }
    });
});
