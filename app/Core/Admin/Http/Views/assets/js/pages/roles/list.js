$(function () {
    function createRolesSortable() {
        let elRoles = document.getElementById('roles');
        let sortableRoles = Sortable.create(elRoles, {
            handle: '.sortable-handle',
            ghostClass: 'ghost',
            animation: 150,
            filter: '.non-draggable',
            draggable: '.draggable',
        });
        return sortableRoles;
    }

    let sortableRoles = createRolesSortable();

    document.querySelector('.chrome-tabs').addEventListener('contentRender', () => {
        sortableRoles = createRolesSortable();
    });

    $(document).on('click', '#saveRoles', (e) => {
        let orderedIds = sortableRoles.toArray();
        saveRoleOrder(orderedIds);
    });

    function saveRoleOrder(orderedIds) {
        let totalRoles = orderedIds.length;
        let priorities = orderedIds.map((id, index) => ({
            id: id,
            priority: totalRoles - index,
        }));

        fetch(u('admin/api/roles/save-order'), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'x-csrf-token': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
            },
            body: JSON.stringify({ order: priorities }),
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

    $(document).on('click', '.roles-group .delete', async function () {
        let roleId = $(this).data('deleterole');
        if (await asyncConfirm(translate('admin.roles.confirm_delete')))
            sendRequest({},u('admin/api/roles/' + roleId), 'DELETE');
    });
});
