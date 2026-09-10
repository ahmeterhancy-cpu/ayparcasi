<?php

/*
|--------------------------------------------------------------------------
| Tiko Sanal POS
|--------------------------------------------------------------------------
| Kaynak: https://docs.tikokart.com — alanlar ve hash formülleri oradaki
| belgeye birebir uyar. Belge değişirse TEK düzeltilecek yer burasıdır.
|
| Seçilen yöntem: 3D Secure ile iFrame Ödeme (`/gateway/onus3D`).
| Alternatifi (`/gateway/pay3d`) kart numarasını BİZİM formumuzdan ister;
| o hâlde kart verisi sunucumuzdan geçer ve PCI-DSS yükümlülüğü büyür.
| iFrame yönteminde kart bilgisi yalnız Tiko'nun sayfasına giriliyor.
|
| Üç kimlik bilgisi var, üçü de Tiko'dan gelir:
|   MerchantId → üye işyeri numarası
|   secret     → "API Anahtarı"; HMAC ANAHTARI olarak kullanılır
|   password   → "Parola"; imzalanacak metnin SONUNA eklenir
*/

return [
    'enabled' => env('TIKO_ENABLED', false),

    // Açıkken kum havuzu adresleri ve IsTest=1 kullanılır.
    'test_mode' => env('TIKO_TEST_MODE', true),

    'merchant_id' => env('TIKO_MERCHANT_ID'),
    'secret' => env('TIKO_SECRET'),
    'password' => env('TIKO_PASSWORD'),

    'base_url' => rtrim((string) env('TIKO_BASE_URL', 'https://www.tikokart.com/api-sanalpos'), '/'),
    'sandbox_base_url' => rtrim((string) env('TIKO_SANDBOX_BASE_URL', 'https://www.tikokart.com/api-sanalpos-sandbox'), '/'),

    /*
     | Uç yolları. iFrame yolu belgede canlıda "onus3D", kum havuzunda
     | "onus3d" yazıyor — büyük/küçük harf farkı büyük ihtimalle dizgi
     | hatası, ama 404 alırsak düzeltilecek yer burası diye ayrı tutuldu.
     */
    'paths' => [
        'iframe' => env('TIKO_PATH_IFRAME', '/gateway/onus3D'),
        'sandbox_iframe' => env('TIKO_PATH_SANDBOX_IFRAME', '/gateway/onus3d'),
        'status' => '/payment/status',
        'cancel' => '/payment/cancel',
    ],

    'currency' => 'TRY',

    // Taksit yok. Belgeye göre taksitli işlemde CardType zorunlu ve
    // bankaya göre alt tutar sınırı var; çiçekçi için gereksiz karmaşa.
    'installment' => '0',

    // Sunucudan sunucuya isteklerde saniye cinsinden zaman aşımı.
    'timeout' => 20,

    /*
     | Ödeme sonuç kodları (belge: Ödeme Sonucu Sorgulama / Callback).
     | Taşıma katmanının Status'ü ile KARIŞTIRMAYIN: dıştaki 200 "sorgu
     | başarılı" demek, ödemenin başarılı olduğunu Result.Status söyler.
     */
    'result' => [
        'pending' => '100',
        'success' => '200',
        'cancelled' => '201',
    ],
];
