<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest $lead, public string $staffName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'فرصت فروش جدید ثبت شد: '.$this->lead->name.' — '.$this->lead->car_label,
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.lead-form-submitted');
    }
}
