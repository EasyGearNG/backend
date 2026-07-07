<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorOrderCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Order $order,
    ) {}

    public function build()
    {
        return $this->subject('Order completed — payment settlement processed')
            ->view('emails.vendor-order-completed', [
                'name'         => $this->user->name,
                'orderRef'     => $this->order->id,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/wallet',
                'appName'      => config('app.name'),
            ]);
    }
}
