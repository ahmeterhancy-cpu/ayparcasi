<?php

namespace App\Mail;

use App\Mail\Concerns\FromShop;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaid extends Mailable
{
    use FromShop, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $orderUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->shopFrom(),
            subject: 'Ödemeniz alındı — '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-paid');
    }
}
