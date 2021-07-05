<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>XLance.ir</title>
        <style>
            body{
                background: #FAFAFA !important;
                font-family: 'Nunito', sans-serif;
                direction: rtl;
                padding: 0;
                margin: 0;
            }
            .box {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                width: 100vw;
                font-size: 1rem;
                line-height: 1.4rem;
                color: #1a202c;
            }
            .box div {
                margin-bottom: 20px;
                color: #1a202c;
            }
            .back {
                display: block;
                text-align: center;
                padding: 10px 15px;
                background: #00C379;
                color: #FAFAFA;
                border-radius: 10px;
                text-decoration: none;
            }
            .back.red {
                background: #DE2147;
            }
        </style>
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    </head>
    <body class="antialiased">
        <div class="box">
            <div>{{ $message }}</div>
            @if($status == 200)
                <div>شماره تراکنش {{$referenceId}}</div>
            @endif
            <a href="{{$url}}" class="back {{$status == 200 ? '' : 'red'}}">بازگشت به سایت</a>
        </div>
    </body>
</html>
