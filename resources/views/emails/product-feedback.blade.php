<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Needs Updates</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .header p { margin: 6px 0 0; font-size: 13px; opacity: 0.85; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 40px 30px; }
        .content h2 { color: #4f46e5; margin-top: 0; }
        .badge { display: inline-block; background-color: #fef3c7; color: #92400e; padding: 6px 16px; border-radius: 999px; font-weight: bold; font-size: 14px; margin-bottom: 20px; }
        .product-name { background-color: #f9fafb; border-left: 4px solid #f59e0b; padding: 12px 20px; margin: 20px 0; font-weight: bold; font-size: 15px; }
        .info { background-color: #f9fafb; border-left: 4px solid #4f46e5; padding: 15px 20px; margin: 20px 0; }
        .info ul { margin: 0; padding-left: 20px; }
        .info li { margin-bottom: 6px; }
        .feedback-box { background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; padding: 15px 20px; margin: 20px 0; }
        .feedback-box strong { color: #92400e; }
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
            <span class="badge">Action Required</span>
            <h2>Dear {{ $name }},</h2>
            <p>Thank you for submitting your product to {{ $appName }}.</p>

            <div class="product-name">{{ $productName }}</div>

            <p>After review, we noticed that your product listing requires some updates before it can be approved and published.</p>

            @if($feedback)
            <div class="feedback-box">
                <strong>Reviewer's Notes:</strong>
                <p style="margin: 8px 0 0;">{{ $feedback }}</p>
            </div>
            @endif

            <p>Please log in to your vendor dashboard to review and update the product information. Common reasons for revision may include:</p>

            <div class="info">
                <ul>
                    <li>Incomplete product details</li>
                    <li>Low-quality images</li>
                    <li>Incorrect category selection</li>
                    <li>Pricing or inventory discrepancies</li>
                </ul>
            </div>

            <p>Once the necessary changes have been made, you may resubmit the product for review.</p>

            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="button">Update Product</a>
            </div>

            <p>Thank you for your cooperation.</p>
            <p>Best regards,<br><strong>The {{ $appName }} Team</strong></p>
        </div>
        <div class="footer">
            <strong>SPORTS SHOPPING SIMPLIFIED</strong><br>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
