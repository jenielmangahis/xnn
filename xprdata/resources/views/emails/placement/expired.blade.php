<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
<div style="font-size: 22px; padding-left: 15px;">
    <p>Dear {{ $sponsor->fname }} {{ $sponsor->lname }},</p>

    <p>This is to notify that you have an unplaced member and will be automatically placed to your 1st Tier after 3 days if not assigned to a new Sponsor.</p>

    <p>
        Details:<br>
        ID: {{ $user->id }}<br>
        Name: {{ $user->fname }} {{ $user->lname }}
    </p>

    <h3>{{ config('app.name') }}&trade;</h3>
</div>
</body>
</html>