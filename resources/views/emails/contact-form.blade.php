<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
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
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f9fafb;
            border-left: 4px solid #4f46e5;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 8px 0;
        }
        .info-box strong {
            color: #4f46e5;
        }
        .message-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .message-box h3 {
            margin-top: 0;
            color: #4f46e5;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>
        
        <div class="content">
            <h2>New Contact Form Submission</h2>
            
            <p>You have received a new message from your website's contact form.</p>
            
            <div class="info-box">
                <p><strong>Name:</strong> {{ $contactName }}</p>
                <p><strong>Email:</strong> <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
                @if($contactPhone)
                <p><strong>Phone:</strong> {{ $contactPhone }}</p>
                @endif
                <p><strong>Date:</strong> {{ now()->format('F d, Y \a\t h:i A') }}</p>
            </div>
            
            <div class="message-box">
                <h3>Message:</h3>
                <p>{{ $contactMessage }}</p>
            </div>
            
            <p style="color: #6b7280; font-size: 14px;">
                You can reply directly to this email to respond to {{ $contactName }}.
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated email from {{ $appName }}.</p>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
