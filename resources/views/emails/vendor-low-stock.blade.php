<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.85; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .badge { display: inline-block; background-color: #fee2e2; color: #991b1b; padding: 6px 16px; border-radius: 999px; font-weight: bold; font-size: 14px; margin-bottom: 20px; }
        .product-box { background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 15px 20px; margin: 20px 0; }
        .product-box .product-name { font-weight: bold; font-size: 15px; color: #92400e; }
        .product-box .stock-count { margin-top: 6px; font-size: 14px; color: #b45309; }
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
            <span class="badge">Low Stock Alert</span>
            <h2>Dear {{ $name }},</h2>
            <p>This is to notify you that one or more of your products are running low on stock.</p>

            <div class="product-box">
                <div class="product-name">{{ $productName }}</div>
                <div class="stock-count">Remaining stock: <strong>{{ $stockCount }} unit(s)</strong></div>
            </div>

            <p>To avoid interruptions in sales and maintain product availability, please update your inventory through your vendor dashboard as soon as possible.</p>

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Update Inventory</a>
            </div>

            <p>Thank you for helping us deliver a seamless shopping experience.</p>
            <p>Best regards,<br><strong>The {{ $appName }} Team</strong></p>
        </div>
        <div class="footer">
            <strong>SPORTS SHOPPING SIMPLIFIED</strong><br>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
