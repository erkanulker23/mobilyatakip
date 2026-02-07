<?php

namespace App\Mail;

use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleNotificationToSupplier extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public Supplier $supplier
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sipariş: ' . $this->sale->saleNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sale-notification-to-supplier',
        );
    }
}
