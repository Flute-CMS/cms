function parseUpdateContent() {
    document.querySelector('.update-container-body-content').innerHTML =
        marked.parse(updateContent);
}

document
    .querySelector('.chrome-tabs')
    .addEventListener('contentRender', ({ detail }) => {
        parseUpdateContent();
    });

parseUpdateContent();

$(document).on('click', '#updateButton', async (e) => {
    let el = $(e.currentTarget);

    if (
        await asyncConfirm(
            translate('admin.update.confirm_update'),
            null,
            translate('def.update'),
            null,
            'primary',
        )
    ) {
        $('.update-modal').attr('open', true);
        $('.bg-update-modal').attr('open', true);

        try {
            const response = await fetch(u('admin/api/update'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'x-csrf-token': $('meta[name="csrf-token"]').attr(
                        'content',
                    ),
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                toast({
                    message: result.success,
                    type: 'success',
                });
            } else {
                toast({
                    message: result.error,
                    type: 'error',
                });
            }
        } catch (error) {
            toast({
                message: error,
                type: 'error',
            });
        } finally {
            $('.update-modal').attr('open', false);
            $('.bg-update-modal').attr('open', false);
        }
    }
});
