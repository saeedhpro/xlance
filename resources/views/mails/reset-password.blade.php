<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ایکس لنس</title>
    <style>
        * {
            direction: rtl;
        }
        body {
            background: #ffffff;
            direction: rtl;
            text-align: right;
        }
        .main {
            height: 100%;
            text-align: right;
            min-width: 400px;
        }
        p {
            direction: rtl;
            font-size: 18px;
            color: #000000;
        }
        a {
            direction: rtl;
            font-size: 18px;
            text-decoration: none;
            display: inline-block !important;
            width: auto;
            padding: 15px 30px;
            border-radius: 15px;
            color: #ffffff !important;
            background: #673ab7;
        }
        .box {
            max-width: 800px;
            background: #f2ebff;
            padding: 50px;
            margin: 35px auto;
            border-radius: 10px;
        }
        .left {
            text-align: left;
        }
        .img {
            margin: 10px auto;
            display: block;
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="box">
            <img class="img" src="https://xlance.ir/images/logo.png" alt="logo">
            <p>
                کاربر {{$username}} برای تغییر پسورد روی لینک زیر کلیک کنید
            </p>
            <a href="{{$route}}">تغییر پسورد</a>
            <p>
                در صورتی که لینک بالا کار نمی کنید لینک زیر را در مرورگر وارد کنید
            </p>
            <a class="left" href="{{$route}}">{{$route}}</a>
        </div>
    </div>
</body>
</html>
