<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductFeedback extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Product $product,
        public ?string $feedback = null,
    ) {}

    public function build()
    {
        return $this->subject('Action required: Your product listing needs updates — ' . config('app.name'))
            ->view('emails.product-feedback', [
                'name'         => $this->user->name,
                'productName'  => $this->product->name,
                'feedback'     => $this->feedback,
                'dashboardUrl' => rtrim(config('frontend.url'), '/') . '/vendor/dashboard',
                'appName'      => config('app.name'),
            ]);
    }
}
