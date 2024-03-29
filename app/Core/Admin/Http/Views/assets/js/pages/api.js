$(document).ready(function () {
    $('#regenerate-btn').click(function () {
        var newKey = generateApiKey(30);
        $('#key').val(newKey);
    });

    function generateApiKey(length) {
        var result = '';
        var characters =
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        for (var i = 0; i < length; i++) {
            result += characters.charAt(
                Math.floor(Math.random() * charactersLength),
            );
        }
        return result;
    }
});
