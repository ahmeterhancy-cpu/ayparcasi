<?php

namespace App\Mail;

use App\Filament\Resources\Orders\OrderResource;
use App\Mail\Concerns\FromShop;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Ekibe giden bildirim. */
class NewOrderAlert extends Mailable
{
    use FromShop, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->shopFrom(),
            subject: 'Yeni sipariş: '.$this->order->number.' · '.money($this->order->total),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-order',
            with: [
                'adminUrl' => OrderResource::getUrl('edit', ['record' => $this->order]),
                'slipUrl' => route('print.slip', $this->order->number),
            ],
        );
    }
}
