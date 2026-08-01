<?php

namespace App\Services;

use App\Mail\NewOrderAlert;
use App\Mail\OrderPaid;
use App\Mail\OrderPlaced;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Sipariş e-postaları.
 *
 * Tek kural: e-posta hiçbir zaman siparişi bozmaz. Gönderim başarısız
 * olursa hata kaydedilir, akış devam eder. Gönderimler yanıt döndükten
 * sonra (defer) çalışır; kuyruk işçisi gerektirmez, müşteriyi bekletmez.
 */
class OrderMailer
{
    public function enabled(): bool
    {
        return (bool) setting('order_emails_enabled', true);
    }

    /** Ekibe giden bildirimlerin adresi. */
    public function teamAddress(): ?string
    {
        $to = setting('order_alert_email') ?: setting('email');

        return filter_var((string) $to, FILTER_VALIDATE_EMAIL) ? (string) $to : null;
    }

    /**
     * Müşterinin siparişi açabileceği imzalı bağlantı.
     * Hesap gerekmez; bağlantı 60 gün geçerlidir.
     */
    public function orderUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'order.magic',
            now()->addDays(60),
            ['order' => $order->number],
        );
    }

    // --- Tetikleyiciler ---------------------------------------------------

    /** Sipariş oluştu: müşteriye özet, ekibe bildirim. */
    public function placed(Order $order): void
    {
        if (! $this->enabled()) {
            return;
        }

        $order->loadMissing('items');

        $this->toCustomer($order, fn () => new OrderPlaced($order, $this->orderUrl($order)));

        if ($team = $this->teamAddress()) {
            $this->send($team, new NewOrderAlert($order), 'yeni sipariş bildirimi');
        }
    }

    /** Sipariş durumu değişti. */
    public function statusChanged(Order $order): void
    {
        if (! $this->enabled() || ! OrderStatusChanged::supports($order->status)) {
            return;
        }

        $order->loadMissing('items');

        $this->toCustomer($order, fn () => new OrderStatusChanged($order, $this->orderUrl($order)));
    }

    /** Ödeme alındı. */
    public function paid(Order $order): void
    {
        if (! $this->enabled()) {
            return;
        }

        $order->loadMissing('items');

        $this->toCustomer($order, fn () => new OrderPaid($order, $this->orderUrl($order)));
    }

    // --- Yardımcılar ------------------------------------------------------

    /** Müşteri e-posta bırakmadıysa sessizce geç. */
    private function toCustomer(Order $order, callable $make): void
    {
        $email = (string) $order->customer_email;

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->send($email, $make(), 'müşteri bildirimi');
    }

    private function send(string $to, $mailable, string $what): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('Sipariş e-postası gönderilemedi', [
                'tur' => $what,
                'alici' => $to,
                'hata' => $e->getMessage(),
            ]);
        }
    }
}
