<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DueSoonLoanReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Loan $loan
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "LibraryHub - Sắp đến hạn trả sách (Phiếu #{$this->loan->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.loans.due-soon-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
