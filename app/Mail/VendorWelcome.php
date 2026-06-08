<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $businessName = null,
    ) {}

    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name') . ' — Vendor account created')
            ->view('emails.vendor-welcome', [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'businessName' => $this->businessName,
                'loginUrl' => rtrim(config('frontend.url'), '/') . '/login',
                'appName' => config('app.name'),
            ]);
    }
}
