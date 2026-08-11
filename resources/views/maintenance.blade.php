{{--
    Yapım aşamasında perdesi.

    Uygulama düzenini (x-layouts.app) BİLEREK kullanmıyor: başlık ve alt bilgi
    kapalı sayfalara bağlantı verir. Burada yalnız marka, açıklama ve hâlâ
    çalışan iletişim kanalları var — dükkân kapalıyken bile sipariş sorulabilsin.
--}}
@php
    $shop = setting('shop_name', 'Ay Parçası');
    $title = setting('maintenance_title', 'Kısa bir ara veriyoruz');
    $message = setting('maintenance_message', 'Sitemizde küçük bir düzenleme yapıyoruz, birazdan yeniden buradayız. Çiçek göndermek isterseniz beklemenize gerek yok: WhatsApp’tan yazın ya da telefonla arayın, buketinizi her zamanki gibi elde hazırlayalım.');
    $until = setting('maintenance_until');
    $phone = setting('phone');
    $instagram = setting('instagram');
    $image = img_url(setting('hero_image'), asset('img/logo.png'));
@endphp
<!DOCTYPE html>
<html lang="tr" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title>{{ $title }} — {{ $shop }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($message), 160) }}">
    <meta name="theme-color" content="#0e2c34">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <script>document.documentElement.classList.replace('no-js','js')</script>

    {{ Vite::fonts() }}
    @vite(['resources/css/app.css'])
</head>
<body class="mtn-body">
    <main class="mtn">
        <div class="mtn__panel">
            <a class="mtn__brand" href="{{ url('/') }}">
                <x-logo class="mtn__mark" />
                <span>
                    <strong>{{ $shop }}</strong>
                    @if (setting('tagline'))
                        <em>{{ setting('tagline') }}</em>
                    @endif
                </span>
            </a>

            <div class="mosaic-rule mtn__rule" aria-hidden="true"></div>

            <p class="eyebrow">Yapım aşamasında</p>
            <h1 class="mtn__title">{{ $title }}</h1>
            <p class="lead">{{ $message }}</p>

            @if ($until)
                <p class="mtn__until">
                    <x-ay-icon name="clock" />
                    <span>{{ $until }}</span>
                </p>
            @endif

            <div class="mtn__actions">
                <a class="btn btn--wa" href="{{ wa_link('Merhaba, sipariş vermek istiyorum.') }}" target="_blank" rel="noopener">
                    <x-ay-icon name="whatsapp" :filled="true" />
                    WhatsApp’tan yaz
                </a>

                @if ($phone)
                    <a class="btn btn--ghost" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">
                        <x-ay-icon name="phone" />
                        {{ $phone }}
                    </a>
                @endif
            </div>

            @if ($instagram)
                <a class="mtn__social" href="{{ $instagram }}" target="_blank" rel="noopener">
                    <x-ay-icon name="instagram" />
                    <span>Yeniliklerimizi Instagram’dan takip edin</span>
                </a>
            @endif
        </div>

        <aside class="mtn__aside" aria-hidden="true">
            <img src="{{ $image }}" alt="" decoding="async">
        </aside>
    </main>
</body>
</html>
