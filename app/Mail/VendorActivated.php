<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorActivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $businessName = null,
    ) {}

    public function build()
    {
        return $this->subject('Your ' . config('app.name') . ' vendor account has been activated')
            ->view('emails.vendor-activated', [
                'name'         => $this->user->name,
                'businessName' => $this->businessName,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/dashboard',
                'appName'      => config('app.name'),
            ]);
    }
}
