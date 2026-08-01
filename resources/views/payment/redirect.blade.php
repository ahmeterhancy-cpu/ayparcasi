<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Güvenli ödemeye yönlendiriliyorsunuz…</title>
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="paywait">
        <div>
            <div class="spinner" aria-hidden="true"></div>
            <h1 style="margin-top:1.5rem">Güvenli ödeme sayfasına gidiyorsunuz</h1>
            <p class="lead" style="margin-top:.7rem">
                Sipariş no: <strong>{{ $order->number }}</strong> · Tutar: <strong>{{ money($order->total) }}</strong>
            </p>
            <p class="muted" style="margin-top:.5rem;font-size:.88rem">
                Sayfa birkaç saniye içinde açılmazsa aşağıdaki düğmeye basın.
            </p>

            <form method="POST" action="{{ $action }}" id="tiko" style="margin-top:1.5rem">
                @foreach ($fields as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <button class="btn btn--lg" type="submit">Ödemeye devam et</button>
            </form>
        </div>
    </div>

    <script>document.getElementById('tiko').submit()</script>
</body>
</html>
