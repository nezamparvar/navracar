<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProformaInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $lead,
        public string $pdfAbsolutePath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'پیش‌فاکتور اولیهٔ هزینهٔ واردات خودروی شما — ناوراکار',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.proforma-invoice');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfAbsolutePath)
                ->as('proforma-navracar-'.$this->lead->id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
