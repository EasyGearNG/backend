# Brevo Email Setup Guide

## What's Been Configured

I've set up your Laravel application to use Brevo (formerly SendinBlue) for sending emails. Here's what was done:

### 1. **Packages Installed**
- `getbrevo/brevo-php` - Official Brevo PHP SDK
- `hofmannsven/laravel-brevo` - Laravel wrapper for Brevo

### 2. **Files Modified/Created**

#### Configuration Files Updated:
- **`config/mail.php`** - Added Brevo as a mail driver option
- **`config/services.php`** - Added Brevo service configuration
- **`.env`** - Added `BREVO_API_KEY` environment variable

#### New Transport Class Created:
- **`app/Mail/Transport/BrevoTransport.php`** - Custom Brevo transport implementation that:
  - Handles email sending via Brevo API
  - Supports To, CC, BCC recipients
  - Supports attachments
  - Handles HTML and text email bodies
  - Supports reply-to addresses

#### Service Provider Updated:
- **`app/Providers/AppServiceProvider.php`** - Registered Brevo transport with Laravel's mail system

## Next Steps

### 1. Get Your Brevo API Key
1. Go to [Brevo](https://www.brevo.com/) and sign up for an account
2. Navigate to Settings → SMTP & API
3. Copy your API Key (v3 API key)

### 2. Update Your `.env` File
Replace the placeholder with your actual API key:
```
BREVO_API_KEY=your_actual_brevo_api_key_here
```

### 3. Switch to Brevo Mailer
Update your `.env` file to use Brevo as the default mailer:
```
MAIL_MAILER=brevo
```

### 4. Test the Configuration
You can test it with an Artisan command:
```bash
php artisan tinker
```

Then in the Tinker shell:
```php
use Illuminate\Support\Facades\Mail;
Mail::raw('Test email body', function ($message) {
    $message->to('your-test-email@example.com')->subject('Test');
});
```

## Usage in Your Application

Your existing mailable classes will work automatically:

```php
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;

class WelcomeMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
        );
    }
}

// Send it
Mail::send(new WelcomeMail());
```

## Brevo Features Available

With this setup, you can use:
- ✅ Transactional emails
- ✅ HTML & text emails
- ✅ Attachments
- ✅ CC/BCC recipients
- ✅ Reply-to addresses
- ✅ Custom headers
- ✅ Tracking (if enabled in Brevo account)

## Fallback Configuration (Optional)

You can set up a failover strategy in your `.env`:
```
MAIL_MAILER=failover
```

And update `config/mail.php` to include:
```php
'failover' => [
    'transport' => 'failover',
    'mailers' => [
        'brevo',
        'log',
    ],
    'retry_after' => 60,
],
```

## Troubleshooting

### Issue: "API Key not found"
- Verify your `BREVO_API_KEY` is set in `.env`
- Run `php artisan config:clear` to refresh configuration cache

### Issue: "Brevo API error"
- Check that your API key is valid and hasn't expired
- Verify your sender email is verified in Brevo
- Check Brevo dashboard for any account restrictions

### Issue: Emails not sending
- Check Laravel logs: `storage/logs/laravel.log`
- Verify sender email address is verified in Brevo
- Ensure your account has sufficient email sending quota

## Documentation
- [Brevo API Documentation](https://developers.brevo.com/docs)
- [Laravel Mail Documentation](https://laravel.com/docs/mail)
