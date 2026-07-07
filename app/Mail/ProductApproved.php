<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Product $product,
    ) {}

    public function build()
    {
        return $this->subject('Your product is now live on ' . config('app.name'))
            ->view('emails.product-approved', [
                'name'         => $this->user->name,
                'productName'  => $this->product->name,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/dashboard',
                'appName'      => config('app.name'),
            ]);
    }
}
