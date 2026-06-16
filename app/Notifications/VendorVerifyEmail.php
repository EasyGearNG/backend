<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VendorVerifyEmail extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        Log::info('Vendor verification email: building message', [
            'user_id' => $notifiable->getKey(),
            'email' => $notifiable->getEmailForVerification(),
            'verification_url' => $verificationUrl,
            'expires_minutes' => Config::get('auth.verification.expire', 60),
            'mail_from' => config('mail.from.address'),
        ]);

        return (new MailMessage)
            ->subject('Verify your email — ' . config('app.name'))
            ->view('emails.vendor-verify-email', [
                'name' => $notifiable->name,
                'verificationUrl' => $verificationUrl,
                'appName' => config('app.name'),
                'expireMinutes' => Config::get('auth.verification.expire', 60),
            ]);
    }

    protected function verificationUrl(object $notifiable): string
    {
        // Generate the API signed URL for verification. Since the 'verification.verify'
        // route has no path placeholders, userId/email are appended as query parameters
        // and included in the signature.
        $apiUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'userId' => $notifiable->getKey(),
                'email' => $notifiable->getEmailForVerification(),
            ]
        );

        // Build the frontend URL using the same query string (userId, email, expires, signature)
        $parsedUrl = parse_url($apiUrl);
        $queryString = $parsedUrl['query'] ?? '';

        $frontendUrl = rtrim(config('frontend.url'), '/') . '/verify-email?' . $queryString;

        return $frontendUrl;
    }
}
