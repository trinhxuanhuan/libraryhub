<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã xác thực đổi mật khẩu LibraryHub'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-change-otp'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
