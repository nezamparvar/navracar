<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QuoteRequest $lead,
        public array $breakdown,
        public array $totals,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'درخواست استعلام قیمت خودرو: '.$this->lead->name.' — '.$this->lead->car_label,
            replyTo: $this->lead->email ? [$this->lead->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.quote-request-received');
    }
}
