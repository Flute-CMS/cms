$(document).ready(function () {
    $('#regenerate-btn').click(function () {
        let newKey = generateApiKey(30);
        $('#key').val(newKey);
    });

    function generateApiKey(length) {
        let result = '';
        let characters =
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let charactersLength = characters.length;
        for (var i = 0; i < length; i++) {
            result += characters.charAt(
                Math.floor(Math.random() * charactersLength),
            );
        }
        return result;
    }
});
