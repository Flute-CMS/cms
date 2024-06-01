$(function () {
    $(document).on('change', '#event_select', updateEventValue);

    $(document).on('input', '#event_other', function () {
        $('#event').val($(this).val());
    });

    function updateEventValue() {
        var selectedValue = $('#event_select').val();
        if (selectedValue === 'other') {
            $('#event_other').show().focus();
            $('#event_other').attr('required', true);
            $('#event').val($('#event_other').val() || '');
        } else {
            $('#event_other').hide();
            $('#event_other').attr('required', false);
            $('#event').val(selectedValue);
        }
    }

    updateEventValue();

    document
        .querySelector('.chrome-tabs')
        .addEventListener('contentRender', ({ detail }) => {
            updateEventValue();
        });

    $(document).on('input', '#title, #icon, #url, #content', function () {
        let titleText = $('#title').val();
        let iconText = $('#icon').val();
        let urlText = $('#url').val();
        let contentText = $('#content').val();

        $('#notification-result .notification_title').text(titleText);
        $('#notification-result .notification_text').text(contentText);

        if (iconText && iconText.includes('<i class')) {
            $('#notification-result i').remove();
            $('.notifications_item_flex').prepend(iconText);
        }

        if (urlText) {
            $('#notification-result .notifications_item_link').remove();

            let notificationLink = $('<a>', {
                class: 'notifications_item_link',
                href: urlText,
                html: translate('def.goto') + ' <i class="ph ph-arrow-right"></i>',
                target: '_blank',
            });

            $('#notification-result .notifications_item')
                .addClass('with_link')
                .append(notificationLink);
        } else {
            $('#notification-result').removeClass('with_link');
            $('#notification-result .notifications_item_link').remove();
        }

        $('#notification-result').addClass('show');
    });
});
