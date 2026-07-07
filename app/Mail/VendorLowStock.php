<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorLowStock extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Product $product,
    ) {}

    public function build()
    {
        return $this->subject('Low stock alert: ' . $this->product->name)
            ->view('emails.vendor-low-stock', [
                'name'         => $this->user->name,
                'productName'  => $this->product->name,
                'stockCount'   => $this->product->quantity,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/products',
                'appName'      => config('app.name'),
            ]);
    }
}
