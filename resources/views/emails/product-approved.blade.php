<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.85; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .badge { display: inline-block; background-color: #d1fae5; color: #065f46; padding: 6px 16px; border-radius: 999px; font-weight: bold; font-size: 14px; margin-bottom: 20px; }
        .product-name { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 12px 20px; margin: 20px 0; font-weight: bold; font-size: 15px; }
        .info { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 15px 20px; margin: 20px 0; }
        .info ul { margin: 0; padding-left: 20px; }
        .info li { margin-bottom: 6px; }
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
            <span class="badge">Product Approved</span>
            <h2>Dear {{ $name }},</h2>
            <p>Great news! Your product has been successfully reviewed and approved by the {{ $appName }} team.</p>

            <div class="product-name">{{ $productName }}</div>

            <p>Your listing is now live on the platform and available for customers to discover and purchase.</p>
            <p>You can log in to your vendor dashboard to:</p>

            <div class="info">
                <ul>
                    <li>View your live products</li>
                    <li>Monitor inventory levels</li>
                    <li>Track customer orders</li>
                    <li>Manage product updates and pricing</li>
                </ul>
            </div>

            <p>Please ensure that your product information, stock levels, and order fulfilment remain up to date to provide customers with the best shopping experience.</p>

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Go to Dashboard</a>
            </div>

            <p>Thank you for partnering with {{ $appName }}.</p>
            <p>Best regards,<br><strong>The {{ $appName }} Team</strong></p>
        </div>
        <div class="footer">
            <strong>SPORTS SHOPPING SIMPLIFIED</strong><br>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
