<?php

/*
|--------------------------------------------------------------------------
| Tiko Sanal POS (Figensoft)
|--------------------------------------------------------------------------
| Tiko'nun herkese açık API dokümanı yoktur; entegrasyon evrakı üye iş yerine
| özel gönderilir. Aşağıdaki alan adları standart 3D Secure form-POST akışına
| göre yazıldı. Evrak eldeyken DEĞİŞTİRİLECEK tek yer burasıdır —
| TikoGateway kodu alan adlarını bu haritadan okur.
*/

return [
    'enabled' => env('TIKO_ENABLED', false),
    'test_mode' => env('TIKO_TEST_MODE', true),

    'merchant_id' => env('TIKO_MERCHANT_ID'),
    'api_key' => env('TIKO_API_KEY'),
    'secret' => env('TIKO_SECRET'),

    'base_url' => rtrim((string) env('TIKO_BASE_URL', 'https://api.tiko.com.tr'), '/'),

    // 3D Secure formunun POST edileceği uç
    'endpoint' => env('TIKO_ENDPOINT', '/payment/3d/init'),

    /*
     | Tiko'ya gönderilen form alanlarının adları.
     | Sol taraf: bizim iç adımız. Sağ taraf: Tiko'nun beklediği alan adı.
     */
    'fields' => [
        'merchant_id' => 'merchant_id',
        'order_id' => 'order_id',
        'amount' => 'amount',
        'currency' => 'currency',
        'ok_url' => 'success_url',
        'fail_url' => 'fail_url',
        'callback_url' => 'callback_url',
        'customer_name' => 'customer_name',
        'customer_email' => 'customer_email',
        'customer_phone' => 'customer_phone',
        'test_mode' => 'test_mode',
        'hash' => 'hash',
    ],

    /*
     | Tiko'nun geri dönüşünde (callback) okuduğumuz alanlar.
     */
    'callback_fields' => [
        'order_id' => 'order_id',
        'status' => 'status',
        'transaction_id' => 'transaction_id',
        'amount' => 'amount',
        'hash' => 'hash',
    ],

    // Callback'te "başarılı" sayılan status değerleri
    'success_values' => ['success', 'SUCCESS', 'approved', '1'],

    // Tutar kuruş cinsinden mi gönderilecek? (12,50 TL -> 1250)
    'amount_in_minor_units' => true,

    'currency' => 'TRY',
];
