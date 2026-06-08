<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { background-color: #f9fafb; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .info { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 15px 20px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ $appName }}</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $name }},</h2>
            <p>Your email has been verified and your vendor account is set up@if($businessName) for <strong>{{ $businessName }}</strong>@endif.</p>
            <div class="info">
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Your account may be reviewed by our team before you can start selling.</li>
                    <li>Once approved, you can log in and add products to your store.</li>
                </ul>
            </div>
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Go to login</a>
            </div>
            <p>Account email: <strong>{{ $email }}</strong></p>
            <p>If you have questions, contact our support team.</p>
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
