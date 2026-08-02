<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public ?string $note = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Satış Fişi: ' . $this->sale->saleNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sale-to-customer',
        );
    }
}
