<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Afiemo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-block;
            background: #10b981;
            position: relative;
            margin-bottom: 30px;
            animation: scaleIn 0.5s ease-in-out;
        }
        
        .checkmark::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 45px;
            border: solid white;
            border-width: 0 5px 5px 0;
            transform: rotate(45deg);
            left: 28px;
            top: 12px;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        h1 {
            color: #1f2937;
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .message {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
        }
        
        .amount {
            color: #10b981;
            font-size: 18px;
            font-weight: 700;
        }
        
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            margin: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .button-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .button-secondary:hover {
            background: #e5e7eb;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .reference {
            margin-top: 20px;
            padding: 15px;
            background: #eff6ff;
            border-radius: 8px;
            font-size: 12px;
            color: #1e40af;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkmark"></div>
        
        <h1>Payment Successful! 🎉</h1>
        
        <p class="message">{{ $message }}</p>
        
        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Order ID</span>
                <span class="detail-value">#{{ $order_id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid</span>
                <span class="detail-value amount">₦{{ number_format($amount, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-value" style="color: #10b981;">Confirmed</span>
            </div>
        </div>
        
        <div>
            <a href="/api/v1/cart" class="button">Continue Shopping</a>
            <a href="/" class="button button-secondary">Go Home</a>
        </div>
        
        <div class="reference">
            <strong>Reference:</strong> {{ $reference }}
        </div>
    </div>
</body>
</html>
