<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueLoanReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Loan $loan
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "LibraryHub - Nhắc trả sách quá hạn (Phiếu #{$this->loan->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.loans.overdue-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
