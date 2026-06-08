<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { background-color: #f9fafb; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .note { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 15px 20px; margin: 20px 0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $name }},</h2>
            <p>Thanks for signing up as a vendor on {{ $appName }}. Please verify your email address to complete your registration.</p>
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verify email address</a>
            </div>
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #4f46e5; font-size: 13px;">{{ $verificationUrl }}</p>
            <div class="note">
                <p>This link expires in {{ $expireMinutes }} minutes. If it has expired, use “Resend verification email” on the signup page.</p>
            </div>
            <p>If you did not create a vendor account, you can ignore this email.</p>
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
