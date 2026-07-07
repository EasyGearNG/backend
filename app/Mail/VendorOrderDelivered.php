<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorOrderDelivered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Order $order,
    ) {}

    public function build()
    {
        return $this->subject('Order successfully delivered — ' . config('app.name'))
            ->view('emails.vendor-order-delivered', [
                'name'         => $this->user->name,
                'orderRef'     => $this->order->id,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/orders',
                'appName'      => config('app.name'),
            ]);
    }
}
