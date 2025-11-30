<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Afiemo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
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
        
        .error-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-block;
            background: #ef4444;
            position: relative;
            margin-bottom: 30px;
            animation: scaleIn 0.5s ease-in-out;
        }
        
        .error-icon::before,
        .error-icon::after {
            content: '';
            position: absolute;
            width: 5px;
            height: 45px;
            background: white;
            left: 37.5px;
            top: 17.5px;
        }
        
        .error-icon::before {
            transform: rotate(45deg);
        }
        
        .error-icon::after {
            transform: rotate(-45deg);
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
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
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
            margin-top: 30px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            font-size: 12px;
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }
        
        .help-text {
            margin-top: 30px;
            padding: 20px;
            background: #eff6ff;
            border-radius: 12px;
            color: #1e40af;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .help-text strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon"></div>
        
        <h1>Payment {{ ucfirst($status ?? 'Failed') }}</h1>
        
        <div class="status-badge">{{ strtoupper($status ?? 'ERROR') }}</div>
        
        <p class="message">{{ $message }}</p>
        
        @if(isset($reference))
        <div class="reference">
            <strong>Reference:</strong> {{ $reference }}
        </div>
        @endif
        
        @if(isset($error))
        <div class="reference" style="background: #fef2f2; color: #991b1b;">
            <strong>Error:</strong> {{ $error }}
        </div>
        @endif
        
        <div style="margin-top: 30px;">
            <a href="/api/v1/cart" class="button">Return to Cart</a>
            <a href="/" class="button button-secondary">Go Home</a>
        </div>
        
        <div class="help-text">
            <strong>Need Help?</strong>
            If you believe this is an error or if your account was debited, please contact our support team with your reference number.
        </div>
    </div>
</body>
</html>
