<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product,
        public int $remaining,
        public int $threshold,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->remaining <= 0
                ? 'Stok bitti: '.$this->product->name
                : 'Stok azaldı: '.$this->product->name.' ('.$this->remaining.' adet)',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.low-stock');
    }
}
