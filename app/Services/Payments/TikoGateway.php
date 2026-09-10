<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tiko Sanal POS — 3D Secure iFrame akışı.
 *
 * Akış:
 *   1) createPaymentLink()  sunucudan sunucuya, Tiko bir ödeme URL'si döner
 *   2) URL iFrame'e konur; kart bilgisi YALNIZ Tiko'nun sayfasına girilir
 *   3) Tiko iFrame içinden UrlOk/UrlFail'e POST eder — bu SONUÇ DEĞİLDİR
 *   4) fetchResult() ile sunucudan sunucuya kesin sonuç sorulur
 *   5) Ayrıca Tiko callback URL'ine JSON gönderir (handleCallback)
 *
 * Belgenin kendisi (docs.tikokart.com) 3. adım için açıkça uyarıyor:
 * yönlendirme işlemin kesin sonucunu garanti etmez. Bu yüzden siparişi
 * "ödendi" yapan tek şey 4. ya da 5. adımdaki doğrulanmış yanıttır.
 */
class TikoGateway
{
    public function isConfigured(): bool
    {
        return (bool) config('tiko.enabled')
            && filled(config('tiko.merchant_id'))
            && filled(config('tiko.secret'))
            && filled(config('tiko.password'));
    }

    public function isTestMode(): bool
    {
        return (bool) config('tiko.test_mode');
    }

    private function baseUrl(): string
    {
        return $this->isTestMode()
            ? (string) config('tiko.sandbox_base_url')
            : (string) config('tiko.base_url');
    }

    private function url(string $path): string
    {
        return $this->baseUrl().$path;
    }

    /**
     * HMAC-SHA256(secret) · mesaj = hashStr + password · base64.
     *
     * Belgedeki referans kod (Hash Oluşturma sayfası) birebir bu:
     * anahtar `secret`, imzalanan metnin SONUNA `password` ekleniyor.
     */
    public function hash(string $hashStr): string
    {
        return base64_encode(hash_hmac(
            'sha256',
            $hashStr.(string) config('tiko.password'),
            (string) config('tiko.secret'),
            true,
        ));
    }

    /** Belgedeki biçim: kuruş hanesi en fazla 2 basamak. Örn "10.00" */
    public function amount(Order $order): string
    {
        return number_format((float) $order->total, 2, '.', '');
    }

    private function isTest(): string
    {
        return $this->isTestMode() ? '1' : '0';
    }

    /**
     * iFrame'e konacak ödeme URL'sini üretir.
     *
     * Hata durumunda null döner ve sebebi günlüğe yazar — çağıran taraf
     * müşteriye "kartla ödeme şu an alınamıyor" der, sipariş kaybolmaz.
     */
    public function createPaymentLink(Order $order): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $merchantId = (string) config('tiko.merchant_id');
        $amount = $this->amount($order);
        $currency = (string) config('tiko.currency');
        $isTest = $this->isTest();
        $urlOk = route('payment.return', $order->number);
        $urlFail = route('payment.return', $order->number);

        $payload = [
            'MerchantId' => $merchantId,
            'OrderId' => $order->number,
            'Amount' => $amount,
            'Currency' => $currency,
            'Installment' => (string) config('tiko.installment'),
            'UrlOk' => $urlOk,
            'UrlFail' => $urlFail,
            'UserName' => (string) $order->customer_name,
            'UserEmail' => (string) $order->customer_email,
            'IsTest' => $isTest,
            // Belgedeki formül: bu uçta Installment ve UserIp YOK.
            'Hash' => $this->hash($merchantId.$order->number.$urlOk.$urlFail.$amount.$currency.$isTest),
        ];

        $path = $this->isTestMode()
            ? (string) config('tiko.paths.sandbox_iframe')
            : (string) config('tiko.paths.iframe');

        try {
            $response = Http::timeout((int) config('tiko.timeout'))
                ->asForm()
                ->post($this->url($path), $payload);
        } catch (\Throwable $e) {
            Log::error('Tiko: ödeme linki alınamadı', [
                'order' => $order->number,
                'hata' => $e->getMessage(),
            ]);

            return null;
        }

        $body = $response->json();
        $link = data_get($body, 'Result.Link');

        if ((string) data_get($body, 'Status') !== '200' || blank($link)) {
            Log::error('Tiko: ödeme linki reddedildi', [
                'order' => $order->number,
                'http' => $response->status(),
                // Kart verisi yok; bu gövde sır taşımıyor.
                'yanit' => $body ?: $response->body(),
            ]);

            return null;
        }

        return (string) $link;
    }

    /**
     * Kesin sonuç: sunucudan sunucuya sorgu.
     *
     * Dönen dizi Tiko'nun `Result` nesnesidir; imza doğrulanamazsa null.
     *
     * @return array<string, mixed>|null
     */
    public function fetchResult(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $merchantId = (string) config('tiko.merchant_id');

        try {
            $response = Http::timeout((int) config('tiko.timeout'))
                ->asForm()
                ->post($this->url((string) config('tiko.paths.status')), [
                    'MerchantId' => $merchantId,
                    'OrderId' => $order->number,
                    'Hash' => $this->hash($merchantId.$order->number),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Tiko: sonuç sorgulanamadı', [
                'order' => $order->number,
                'hata' => $e->getMessage(),
            ]);

            return null;
        }

        $body = $response->json();

        /*
         * Dıştaki Status "sorgu başarılı mı" demek; ödemenin sonucunu
         * Result.Status söylüyor. İkisini karıştırmak başarısız bir ödemeyi
         * başarılı saymak olurdu — belge bu ayrımı özellikle vurguluyor.
         */
        if ((string) data_get($body, 'Status') !== '200') {
            Log::warning('Tiko: sonuç sorgusu reddedildi', [
                'order' => $order->number,
                'yanit' => $body ?: $response->body(),
            ]);

            return null;
        }

        $result = (array) data_get($body, 'Result', []);

        if (! $this->verifyResultHash($result)) {
            Log::warning('Tiko: sorgu yanıtının imzası doğrulanamadı', [
                'order' => $order->number,
            ]);

            return null;
        }

        return $result;
    }

    /** Sunucudan sunucuya sorup sonucu siparişe işler. */
    public function syncOrder(Order $order): Order
    {
        $result = $this->fetchResult($order);

        return $result ? $this->apply($order, $result) : $order;
    }

    /**
     * Tiko'nun callback servisinden gelen JSON gövdesi.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): ?Order
    {
        $orderNumber = (string) ($payload['OrderId'] ?? '');
        $order = Order::where('number', $orderNumber)->first();

        if (! $order) {
            Log::warning('Tiko callback: sipariş bulunamadı', ['order' => $orderNumber]);

            return null;
        }

        if (! $this->verifyResultHash($payload)) {
            Log::warning('Tiko callback: imza doğrulanamadı', ['order' => $orderNumber]);

            return null;
        }

        return $this->apply($order, $payload);
    }

    /**
     * Doğrulanmış sonucu siparişe yazar.
     *
     * @param  array<string, mixed>  $result
     */
    private function apply(Order $order, array $result): Order
    {
        // Ödenmiş siparişe bir daha dokunulmaz: aynı sonuç birden çok kez
        // gelebilir (callback yeniden dener, müşteri sayfayı tazeler) ve
        // geri alınması sipariş geçmişini bozardı.
        if ($order->payment_status === 'paid') {
            return $order;
        }

        // Tutar oynanmış mı? Sipariş toplamıyla birebir tutmalı.
        if (isset($result['Amount'])
            && number_format((float) $result['Amount'], 2, '.', '') !== $this->amount($order)) {
            Log::warning('Tiko: tutar uyuşmuyor', [
                'order' => $order->number,
                'gelen' => $result['Amount'],
                'beklenen' => $this->amount($order),
            ]);

            return $order;
        }

        $status = (string) ($result['Status'] ?? '');
        $codes = config('tiko.result');

        // "Henüz belli değil" bir karar DEĞİL — siparişi başarısıza
        // düşürmeden yalnız ham yanıtı saklıyoruz.
        if ($status === $codes['pending']) {
            $order->update(['payment_payload' => $result]);

            return $order;
        }

        $success = $status === $codes['success'];

        $order->update([
            'payment_status' => $success ? 'paid' : 'failed',
            'status' => $success ? 'confirmed' : $order->status,
            'paid_at' => $success ? now() : null,
            'payment_reference' => (string) ($result['TransId'] ?? ''),
            'payment_payload' => $result,
        ]);

        return $order;
    }

    /**
     * Yanıt imzası: MerchantId + OrderId + Amount + Currency + Installment
     *                + Status + TransId
     *
     * @param  array<string, mixed>  $r
     */
    public function verifyResultHash(array $r): bool
    {
        $given = (string) ($r['Hash'] ?? '');

        if ($given === '' || (string) ($r['MerchantId'] ?? '') !== (string) config('tiko.merchant_id')) {
            return false;
        }

        $prefix = (string) config('tiko.merchant_id').(string) ($r['OrderId'] ?? '');
        $middle = (string) ($r['Currency'] ?? '');
        $suffix = (string) ($r['Status'] ?? '').(string) ($r['TransId'] ?? '');

        /*
         * Tutar ve taksit JSON'da SAYI olarak geliyor ("Amount": 10) ama
         * hash metin birleştirmesi. Tiko'nun bunları hangi yazımla
         * imzaladığı belgede yazmıyor — "10", "10.00", "10,00" hepsi
         * olabilir. Aynı DEĞERİN makul yazımlarını tek tek deniyoruz; bu
         * bir gevşetme değil, doğru biçimi bulmak. Hiçbiri tutmazsa imza
         * reddediliyor. İlk gerçek işlemden sonra hangi biçimin tuttuğu
         * günlüğe yazılıyor; istenirse buraya sabitlenebilir.
         */
        foreach ($this->numberForms($r['Amount'] ?? '') as $amount) {
            foreach ($this->numberForms($r['Installment'] ?? '') as $installment) {
                if (hash_equals($this->hash($prefix.$amount.$middle.$installment.$suffix), $given)) {
                    Log::info('Tiko: imza doğrulandı', [
                        'order' => $r['OrderId'] ?? null,
                        'amount_bicimi' => $amount,
                        'installment_bicimi' => $installment,
                    ]);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Bir sayının makul metin yazımları.
     *
     * @return list<string>
     */
    private function numberForms(mixed $value): array
    {
        $number = (float) $value;
        $twoDecimals = number_format($number, 2, '.', '');

        return array_values(array_unique([
            is_string($value) ? $value : (string) $value,
            $twoDecimals,
            rtrim(rtrim($twoDecimals, '0'), '.'),
            str_replace('.', ',', $twoDecimals),
        ]));
    }
}
