<!DOCTYPE html>
<html>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <!--[if (mso 9)]>
        <style type="text/css">
            body{font-family: 'Helvetica Neue', Helvetica, Arial, 'sans-serif' !important;}
        </style>
    <![endif]-->
    <style>
        body {
            font-family: 'Montserrat', 'Helvetica Neue', Helvetica, Arial, 'sans-serif';
            background-color: #151515;
            color: #fff;
            padding: 20px;
        }

        .container {
            background-color: #151515;
            padding: 8rem 0;
            border-radius: 5px;
        }

        .block {
            max-width: 600px;
            color: #ffffffb2;
            margin: 0 auto;
            border-radius: 5px;
            overflow: hidden;
            background-color: #1c1c1c;
        }

        .header {
            background-color: #272727;
            padding: 20px;
            color: #fff
        }

        h1 {
            margin: 10px;
            line-height: 1;
        }

        .content {
            padding: 20px;
        }

        .white_text {
            color: #fff
        }

        .button {
            display: inline-block;
            background-color: #BAFF68;
            color: #000 !important;
            padding: 10px 20px;
            text-decoration: none;
            margin: 10px auto;
            border-radius: 50px;
            transition: background-color 0.3s ease;
            font-weight: bold;
            font-family: 'Montserrat', sans-serif;
        }

        .button:hover {
            background-color: #a0da59;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="block">
            <div class="header">
                <h1>Сброс пароля</h1>
            </div>
            <div class="content">
                <p class="white_text">Уважаемый(ая) {{ $name }},</p>
                <p>Мы получили запрос на сброс пароля для вашего аккаунта. Если вы не отправляли этот запрос, просто
                    проигнорируйте это сообщение. В противном случае, вы можете сбросить свой пароль, нажав на кнопку
                    ниже.</p>
                <a href="{{ $url }}" class="button">Сбросить пароль</a>
                <p class="white_text">С уважением,<br />{{ app('app.name') }}</p>
            </div>
        </div>
    </div>
</body>

</html>
