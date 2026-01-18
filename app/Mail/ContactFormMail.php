<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactName;
    public $contactEmail;
    public $contactPhone;
    public $contactMessage;
    public $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $email, ?string $phone, string $message)
    {
        $this->contactName = $name;
        $this->contactEmail = $email;
        $this->contactPhone = $phone;
        $this->contactMessage = $message;
        $this->appName = env('APP_NAME', 'Easy Gear');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Contact Form Submission - ' . $this->appName)
                    ->replyTo($this->contactEmail, $this->contactName)
                    ->view('emails.contact-form');
    }
}
