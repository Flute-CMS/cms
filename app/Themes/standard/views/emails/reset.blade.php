<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>@t('auth.reset.subject')</title>
</head>

<body style="margin:0; padding:0; background-color:#0F0F0F; color:#F1F1F1; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#0F0F0F">
        <tr>
            <td align="center">
                <table width="700" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:700px; margin:0 auto;">
                    <tr>
                        <td style="padding:20px; text-align:center;">
                            <h1 style="color:#a0da59; margin:10px; line-height:1;">@t('auth.reset.subject')</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#1A1A1A; border-radius:10px; padding:40px; color:#ffffffb2;">
                            <h2 style="color:#F1F1F1; text-align:center; margin-bottom:30px;">@t('auth.dear', [':name' => $name])</h2>
                            <p style="margin-bottom:15px;">@t('auth.reset.message')</p>
                            <table align="center" cellpadding="0" cellspacing="0" border="0"
                                style="margin:20px auto;">
                                <tr>
                                    <td style="background-color:#BAFF68; border-radius:5px; text-align:center;">
                                        <a href="{{ $url }}"
                                            style="display:block; padding:12px 24px; color:#000000; text-decoration:none; font-weight:500; font-size:14px;">@t('auth.reset.subject')</a>
                                    </td>
                                </tr>
                            </table>
                            <small
                                style="display:block; opacity:0.6; font-size:12px; text-align:center;">@t('auth.with_best', [':name' => app('app.name')])</small>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px; text-align:center;">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
