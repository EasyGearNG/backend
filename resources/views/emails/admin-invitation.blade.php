<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #4f46e5;
            margin-top: 0;
        }
        .credentials {
            background-color: #f9fafb;
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .credentials p {
            margin: 8px 0;
        }
        .credentials strong {
            color: #4f46e5;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #4338ca;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to {{ $appName }}</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $name }},</h2>
            
            <p>You have been invited to join {{ $appName }} as an <strong>Administrator</strong>.</p>
            
            <p>Your administrator account has been created with the following credentials:</p>
            
            <div class="credentials">
                <p><strong>Email:</strong> {{ $email }}</p>
                <p><strong>Temporary Password:</strong> {{ $password }}</p>
            </div>
            
            <div class="warning">
                <p><strong>Important Security Notice:</strong></p>
                <p>Please change your password immediately after your first login for security purposes.</p>
            </div>
            
            <p>Click the button below to access the admin panel:</p>
            
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Login to Admin Panel</a>
            </div>
            
            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #4f46e5;">{{ $loginUrl }}</p>
            
            <p>As an administrator, you will have access to:</p>
            <ul>
                <li>Dashboard and analytics</li>
                <li>User management</li>
                <li>Order management</li>
                <li>Product and vendor management</li>
                <li>Payment tracking</li>
                <li>And more...</li>
            </ul>
            
            <p>If you have any questions or need assistance, please contact our support team.</p>
            
            <p>Best regards,<br>The {{ $appName }} Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
