document.addEventListener('DOMContentLoaded', function () {
    let el = document.getElementById('roles');
    let sortable = Sortable.create(el, {
        handle: '.sortable-handle',
        ghostClass: 'ghost',
        animation: 150,
        filter: '.non-draggable',
        draggable: '.draggable',
    });

    $('#save').on('click', (e) => {
        let orderedIds = sortable.toArray();
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
                "x-csrf-token":document
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
        let roleId = $(this).data('deleterole');
        if (confirm(translate('admin.roles.confirm_delete')))
            ajaxModuleAction(u('admin/api/roles/' + roleId), 'DELETE');
    });
});
