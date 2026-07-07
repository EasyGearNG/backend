<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.85; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .steps { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 15px 20px; margin: 20px 0; }
        .steps ol { margin: 0; padding-left: 20px; }
        .steps li { margin-bottom: 10px; }
        .steps ul { margin: 8px 0 0; padding-left: 20px; }
        .steps ul li { margin-bottom: 4px; }
        .button { display: inline-block; padding: 12px 30px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { background-color: #f9fafb; padding: 20px 30px; text-align: center; font-size: 12px; color: #6b7280; }
        .footer strong { letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
            <p>Sports Shopping Simplified</p>
        </div>
        <div class="content">
            <h2>Dear {{ $name }},</h2>
            <p>Welcome to <strong>{{ $appName }}</strong> — Sports Shopping Simplified.</p>
            <p>Your vendor account has been successfully created. To begin selling on our platform, please complete the following steps:</p>

            <div class="steps">
                <ol>
                    <li>Verify your email address by clicking the verification link sent to your inbox.</li>
                    <li>Log in to your vendor dashboard.</li>
                    <li>Complete your business profile and payment information.</li>
                    <li>Upload your products by providing:
                        <ul>
                            <li>Product category</li>
                            <li>Price</li>
                            <li>SKU</li>
                            <li>Stock quantity</li>
                            <li>Weight</li>
                            <li>Product description</li>
                            <li>Product images</li>
                        </ul>
                    </li>
                </ol>
            </div>

            <p>All submitted products will undergo a brief review before they go live on the marketplace.</p>
            <p>Once approved, your products will become visible to customers across the <strong>{{ $appName }}</strong> platform.</p>
            <p>If you require assistance at any stage, our support team is available to help.</p>

            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Go to Dashboard</a>
            </div>

            <p>Thank you for choosing {{ $appName }}.</p>
            <p>Best regards,<br><strong>The {{ $appName }} Team</strong></p>
        </div>
        <div class="footer">
            <strong>SPORTS SHOPPING SIMPLIFIED</strong><br>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
