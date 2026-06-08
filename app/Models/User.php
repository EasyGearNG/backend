<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\VendorVerifyEmail;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, MustVerifyEmailTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'phone_number',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's addresses.
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get the user's orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the user's cart.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get the user's reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the user's wishlist items.
     */
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the vendor profile for this user (if user is a vendor)
     */
    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Send the email verification notification (vendors).
     */
    public function sendEmailVerificationNotification(): void
    {
        Log::info('Vendor verification email: preparing to send', [
            'user_id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'mail_mailer' => config('mail.default'),
            'mail_from' => config('mail.from.address'),
            'app_url' => config('app.url'),
        ]);

        try {
            $this->notify(new VendorVerifyEmail);

            Log::info('Vendor verification email: sent successfully', [
                'user_id' => $this->id,
                'email' => $this->email,
                'mail_mailer' => config('mail.default'),
                'note' => config('mail.default') === 'log'
                    ? 'MAIL_MAILER=log — email written to storage/logs/laravel.log, not delivered to inbox'
                    : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Vendor verification email: send failed', [
                'user_id' => $this->id,
                'email' => $this->email,
                'mail_mailer' => config('mail.default'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send the password reset notification with custom URL
     */
    public function sendPasswordResetNotification($token)
    {
        $url = config('frontend.url', config('app.url')) . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);
        
        $this->notify(new ResetPasswordNotification($token));
    }
}
