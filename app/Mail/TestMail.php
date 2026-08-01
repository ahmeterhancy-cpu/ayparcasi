<?php

namespace App\Mail;

use App\Mail\Concerns\FromShop;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Ayarlar sayfasındaki "Test e-postası gönder" işlemi için. */
class TestMail extends Mailable
{
    use FromShop;

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->shopFrom(),
            subject: 'Test e-postası — '.setting('shop_name', 'Ay Parçası'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test');
    }
}
