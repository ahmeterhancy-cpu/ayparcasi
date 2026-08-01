<?php

namespace App\Mail;

use App\Mail\Concerns\FromShop;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged extends Mailable
{
    use FromShop, Queueable, SerializesModels;

    /**
     * Her durum için müşteriye söylenecek şey.
     * Burada olmayan durumlar için e-posta gönderilmez.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const MESSAGES = [
        'confirmed' => [
            'Siparişiniz onaylandı',
            'her şey yolunda — teslim gününde hazır olacak.',
            '#16697f',
        ],
        'preparing' => [
            'Buketiniz hazırlanıyor',
            'tasarımınız şu anda tezgâhta, elde hazırlanıyor.',
            '#4cbfc4',
        ],
        'on_the_way' => [
            'Siparişiniz yolda',
            'kuryemiz yola çıktı, kısa süre içinde alıcıda olacak.',
            '#f4b02a',
        ],
        'delivered' => [
            'Siparişiniz teslim edildi',
            'çiçekleriniz alıcıya ulaştı. Umarız beğenilmiştir.',
            '#2f7d5c',
        ],
        'cancelled' => [
            'Siparişiniz iptal edildi',
            'sipariş iptal edildi.',
            '#db4a32',
        ],
    ];

    public function __construct(
        public Order $order,
        public string $orderUrl,
    ) {}

    public static function supports(string $status): bool
    {
        return isset(self::MESSAGES[$status]);
    }

    public function envelope(): Envelope
    {
        [$headline] = self::MESSAGES[$this->order->status];

        return new Envelope(
            from: $this->shopFrom(),
            subject: $headline.' — '.$this->order->number,
        );
    }

    public function content(): Content
    {
        [$headline, $body, $accent] = self::MESSAGES[$this->order->status];

        return new Content(
            view: 'emails.order-status',
            with: compact('headline', 'body', 'accent'),
        );
    }
}
