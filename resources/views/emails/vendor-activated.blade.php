<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Activated</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { background-color: #f9fafb; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .badge { display: inline-block; background-color: #d1fae5; color: #065f46; padding: 6px 16px; border-radius: 999px; font-weight: bold; font-size: 14px; margin-bottom: 20px; }
        .info { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 15px 20px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>
        <div class="content">
            <span class="badge">Account Activated</span>
            <h2>Great news, {{ $name }}!</h2>
            <p>Your vendor account{!! $businessName ? ' for <strong>' . e($businessName) . '</strong>' : '' !!} has been reviewed and <strong>activated</strong>. You can now log in and start selling on {{ $appName }}.</p>
            <div class="info">
                <p><strong>You now have access to:</strong></p>
                <ul>
                    <li>Add and manage your products</li>
                    <li>View and fulfil customer orders</li>
                    <li>Track your wallet balance and request payouts</li>
                </ul>
            </div>
            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Go to your dashboard</a>
            </div>
            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
