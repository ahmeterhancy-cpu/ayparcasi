<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Tiko sanal POS — 3D Secure form-POST akışı.
 *
 * Akış:
 *   1) start()  → müşteriyi Tiko'ya yönlendiren otomatik gönderimli form üretir
 *   2) Tiko 3D doğrulamasını yapar, sonucu callback'e POST eder
 *   3) handleCallback() → imzayı doğrular, siparişi öder/başarısız işaretler
 *
 * Alan adları config/tiko.php'den okunur; Tiko entegrasyon evrakı gelince
 * yalnızca o dosya güncellenir.
 */
class TikoGateway
{
    public function isConfigured(): bool
    {
        return (bool) config('tiko.enabled')
            && filled(config('tiko.merchant_id'))
            && filled(config('tiko.secret'));
    }

    public function endpoint(): string
    {
        return config('tiko.base_url').config('tiko.endpoint');
    }

    /**
     * Tiko'ya POST edilecek form alanları (imza dahil).
     *
     * @return array<string, string>
     */
    public function formFields(Order $order): array
    {
        $f = config('tiko.fields');
        $amount = $this->amount($order);

        $payload = [
            $f['merchant_id'] => (string) config('tiko.merchant_id'),
            $f['order_id'] => $order->number,
            $f['amount'] => $amount,
            $f['currency'] => config('tiko.currency'),
            $f['ok_url'] => route('payment.return', $order->number),
            $f['fail_url'] => route('payment.return', $order->number),
            $f['callback_url'] => route('payment.callback'),
            $f['customer_name'] => $order->customer_name,
            $f['customer_email'] => (string) $order->customer_email,
            $f['customer_phone'] => $order->customer_phone,
            $f['test_mode'] => config('tiko.test_mode') ? '1' : '0',
        ];

        $payload[$f['hash']] = $this->signature($order->number, $amount);

        return $payload;
    }

    /**
     * Tutarı Tiko'nun beklediği biçimde döndürür.
     */
    private function amount(Order $order): string
    {
        $total = (float) $order->total;

        return config('tiko.amount_in_minor_units')
            ? (string) (int) round($total * 100)
            : number_format($total, 2, '.', '');
    }

    /**
     * HMAC-SHA256 imza. Tiko evrakında imza sırası farklıysa
     * yalnızca bu metot güncellenir.
     */
    public function signature(string $orderNumber, string $amount): string
    {
        $raw = implode('|', [
            (string) config('tiko.merchant_id'),
            $orderNumber,
            $amount,
            (string) config('tiko.currency'),
            config('tiko.test_mode') ? '1' : '0',
        ]);

        return base64_encode(hash_hmac('sha256', $raw, (string) config('tiko.secret'), true));
    }

    /**
     * Tiko'dan gelen bildirimi doğrular ve siparişi günceller.
     * Zaten ödenmiş sipariş yeniden işlenmez (tekrar bildirim güvenliği).
     */
    public function handleCallback(Request $request): ?Order
    {
        $c = config('tiko.callback_fields');

        $orderNumber = (string) $request->input($c['order_id']);
        $status = (string) $request->input($c['status']);
        $amount = (string) $request->input($c['amount']);
        $hash = (string) $request->input($c['hash']);

        $order = Order::where('number', $orderNumber)->first();

        if (! $order) {
            Log::warning('Tiko callback: sipariş bulunamadı', ['order' => $orderNumber]);

            return null;
        }

        $expected = $this->signature($orderNumber, $amount);

        if (! hash_equals($expected, $hash)) {
            Log::warning('Tiko callback: imza doğrulanamadı', ['order' => $orderNumber]);

            $order->update([
                'payment_payload' => $request->all(),
            ]);

            return null;
        }

        // Tutar oynanmış mı?
        if ($amount !== $this->amount($order)) {
            Log::warning('Tiko callback: tutar uyuşmuyor', [
                'order' => $orderNumber,
                'gelen' => $amount,
                'beklenen' => $this->amount($order),
            ]);

            return null;
        }

        if ($order->payment_status === 'paid') {
            return $order; // tekrar bildirim — hiçbir şeyi değiştirme
        }

        $success = in_array($status, config('tiko.success_values'), true);

        $order->update([
            'payment_status' => $success ? 'paid' : 'failed',
            'status' => $success ? 'confirmed' : $order->status,
            'paid_at' => $success ? now() : null,
            'payment_reference' => (string) $request->input($c['transaction_id']),
            'payment_payload' => $request->all(),
        ]);

        return $order;
    }
}
