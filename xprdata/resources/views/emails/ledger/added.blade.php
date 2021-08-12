<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
<div style="font-size: 22px; padding-left: 15px;">

    <p>Dear {{ $user->fname }} {{ $user->lname }},</p>

    <p>Funds were added to your ledger, login <a href="{{ config('app.vo_url') }}">here</a> to review. </p>

    <p>{{ config('app.vo_url') }}</p>

    <h3>{{ config('app.name') }}&trade;</h3>
</div>
</body>
</html>