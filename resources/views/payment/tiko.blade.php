{{--
    Kart ödeme ekranı.

    Kart numarası bu sayfaya GİRİLMEZ: iFrame'in içindeki her şey Tiko'nun
    kendi alan adında. Bizim sunucumuz kart verisini ne görür ne saklar.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Güvenli ödeme — {{ setting('shop_name', 'Ay Parçası') }}</title>
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="paywait">
        <div>
            <h1>Güvenli ödeme</h1>
            <p class="lead" style="margin-top:.7rem">
                Sipariş no: <strong>{{ $order->number }}</strong> ·
                Tutar: <strong>{{ money($order->total) }}</strong>
            </p>

            @if (app(\App\Services\Payments\TikoGateway::class)->isTestMode())
                <p class="muted" style="margin-top:.5rem;font-size:.85rem">
                    <strong>TEST ORTAMI</strong> — bu işlemde gerçek para çekilmez.
                </p>
            @endif

            <div class="payframe">
                <iframe
                    src="{{ $link }}"
                    title="Tiko güvenli ödeme formu"
                    allow="payment"
                    referrerpolicy="origin"></iframe>
            </div>

            <p class="muted" style="margin-top:1rem;font-size:.85rem">
                Kart bilgileriniz doğrudan Tiko'ya iletilir, bizim sunucumuza
                düşmez. Ödeme ekranı açılmazsa
                <a href="{{ route('order.show', $order->number) }}">sipariş sayfanıza</a>
                dönüp bizimle iletişime geçebilirsiniz.
            </p>
        </div>
    </div>
</body>
</html>
