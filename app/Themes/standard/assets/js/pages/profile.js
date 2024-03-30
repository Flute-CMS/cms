$(document).ready(function() {
    // Banner update method
    $('#profile_banner_change').click(function(e){
        e.preventDefault();
        $('#banner-input').click();
    });

    $('#banner-input').change(function(){
        let file = this.files[0]; 
        if (file) {
            let formData = new FormData();
            formData.append('banner', file);
            uploadImage('profile/edit/banner', formData, '.profile_banner', true, '.progress-bar');
        }
    });

    // Avatar update method
    $('#profile_avatar_change').click(function(e){
        e.preventDefault();
        $('#avatar-input').click();
    });

    $('#avatar-input').change(function(){
        let file = this.files[0]; 
        if (file) {
            let formData = new FormData();
            formData.append('avatar', file);
            uploadImage('profile/edit/avatar', formData, '.profile_avatar', false, '.avatar-loading-indicator');
        }
    });
});

function uploadImage(url, formData, imageClass, isBanner, progressClass) {
    $(progressClass).css('width', '0%');
    $(progressClass).show();

    if( !isBanner )
        $(progressClass).css('left', '0');

    $.ajax({
        url: u(url),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            var xhr = $.ajaxSettings.xhr();
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percent = Math.floor((e.loaded / e.total) * 50);  // Half of progress bar for upload
                    $(progressClass).css('width', percent + '%');
                }
            };

            // Monitor the progress of the response
            xhr.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percent = 50 + Math.floor((e.loaded / e.total) * 50);  // Second half of progress bar for response
                    $(progressClass).css('width', percent + '%');
                }
            };

            return xhr;
        },
        success: async function(response) {
            $(progressClass).css('width', '100%');  // Full progress on success

            $(progressClass).fadeOut(300);

            setTimeout(() => {
                if( isBanner )
                    $(progressClass).css('left', '-20px');
            }, 300);

            if (response.success) {
                if (isBanner) {
                    $(imageClass).css('background', 'url(' + response.success + ') center center / cover no-repeat');
                } else {
                    $('.mini_avatar').attr('src', response.success);
                    $(imageClass).attr('src', response.success);
                }

                toast({
                    message: translate('def.success'),
                    type: 'success'
                });
            } else {
                toast({
                    message: response?.error,
                    type: 'error'
                });
            }
        },
        error: function(response) {
            // Hide loading indicator
            $(progressClass).css('width', '0%');
            $(progressClass).fadeOut(300);

            setTimeout(() => {

                if( isBanner )
                    $(progressClass).css('left', '-20px');
            }, 300);

            if( response?.responseJSON?.error?.message || typeof response?.responseJSON?.error === 'string' )
                toast({
                    message: typeof response?.responseJSON?.error === 'string' ? response?.responseJSON?.error : response?.responseJSON?.error?.message,
                    type: 'error'
                });
        }
    });
}
