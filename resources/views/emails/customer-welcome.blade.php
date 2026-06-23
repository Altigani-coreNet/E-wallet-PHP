<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome</title>
</head>
<body>
    <p>Hello {{ $customerName }},</p>
    <p>Welcome to {{ config('app.name') }}! Your profile has been completed successfully.</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Phone:</strong> {{ $phone }}</p>
    <p>Thank you for joining us.</p>
</body>
</html>
