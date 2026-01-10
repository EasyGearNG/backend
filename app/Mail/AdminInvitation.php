<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $password;
    public $loginUrl;
    public $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password)
    {
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = $password;
        $this->loginUrl = env('APP_URL', 'http://localhost') . '/login';
        $this->appName = env('APP_NAME', 'Easy Gear');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Admin Access Invitation - ' . $this->appName)
                    ->view('emails.admin-invitation');
    }
}
