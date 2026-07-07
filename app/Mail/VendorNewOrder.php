<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorNewOrder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Order $order,
    ) {}

    public function build()
    {
        return $this->subject('New order received — ' . config('app.name'))
            ->view('emails.vendor-new-order', [
                'name'         => $this->user->name,
                'orderRef'     => $this->order->id,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/orders',
                'appName'      => config('app.name'),
            ]);
    }
}
