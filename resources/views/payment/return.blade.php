{{--
    Tiko'nun dönüş sayfası. iFrame'İN İÇİNDE açılır, o yüzden tek işi
    üst pencereyi sipariş sayfasına götürmek.

    Neden yönlendirme (redirect) değil de bu sayfa: yönlendirme de iFrame
    içinde kalırdı, müşteri sipariş sayfasını 450 piksellik bir çerçevenin
    içinde görürdü.

    Neden `window.top`: çerez SameSite=Lax. Tiko'nun bu sayfaya yaptığı POST
    siteler arası olduğu için oturum çerezi GELMEZ — burada oturuma
    bakamayız. Üst pencereyi kendi adresimize götürünce istek birinci
    seviye GET olur ve çerez gider; sipariş sayfası müşteriyi tanır.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Ödeme sonucu</title>
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css'])
    <script>
        // JS kapalıysa aşağıdaki bağlantı devrede (target="_top").
        (window.top || window).location.href = @json($url);
    </script>
</head>
<body>
    <div class="paywait">
        <div>
            <div class="spinner" aria-hidden="true"></div>
            <h1 style="margin-top:1.5rem">
                {{ $order->payment_status === 'paid' ? 'Ödemeniz alındı' : 'Ödeme sonucunuz kontrol ediliyor' }}
            </h1>
            <p class="lead" style="margin-top:.7rem">Sipariş sayfanıza dönüyorsunuz…</p>
            <p style="margin-top:1.2rem">
                <a class="btn" href="{{ $url }}" target="_top">Siparişimi göster</a>
            </p>
        </div>
    </div>
</body>
</html>
