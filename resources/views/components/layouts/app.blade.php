@props([
    'title' => null,
    'description' => null,
    'transparentHeader' => false,
    'bodyClass' => '',
])

<!DOCTYPE html>
<html lang="tr" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Aynı gün gönderim kapanışı (gün içi dakika) — kart sayaçlarını besler --}}
    <meta name="ship-cutoff" content="{{ (int) setting('same_day_cutoff_hour', 15) * 60 }}">

    <title>{{ $title ? $title.' — '.setting('shop_name', 'Ay Parçası') : setting('shop_name', 'Ay Parçası').' — '.setting('tagline', 'Hediyelik Tasarımlar & Çiçekçi Dükkanı') }}</title>
    <meta name="description" content="{{ $description ?: setting('meta_description', 'Kıbrıs\'ta el yapımı buketler, orkideler ve hediyelik tasarımlar. Aynı gün teslimat.') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ setting('shop_name', 'Ay Parçası') }}">
    <meta property="og:title" content="{{ $title ?: setting('shop_name', 'Ay Parçası') }}">
    <meta property="og:description" content="{{ $description ?: setting('meta_description', 'Kıbrıs\'ta el yapımı buketler ve hediyelik tasarımlar.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#0e2c34">
    <link rel="canonical" href="{{ url()->current() }}">
    {{-- Simgeler gerçek logonun işaretinden üretildi (public/img/mark.png).
         apple-touch-icon saydam değil krem zeminli — iOS saydamlığı siyaha çevirir. --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Hareket başlamadan önce .js işaretle — açılışta yanıp sönmeyi önler --}}
    <script>document.documentElement.classList.replace('no-js','js')</script>

    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body
    class="{{ $bodyClass }}"
    @auth
        data-auth="1"
        data-fav-url="{{ url('/hesabim/favorilerim') }}"
        data-fav-merge="{{ route('account.favorites.merge') }}"
    @endauth
>
    <a class="skip-link" href="#icerik">İçeriğe geç</a>

    @include('partials.header', ['transparent' => $transparentHeader])

    <main id="icerik">
        {{ $slot }}
    </main>

    @include('partials.footer')

    @include('partials.toasts')

    <a
        class="wa-float"
        href="{{ wa_link('Merhaba, Ay Parçası\'ndan bilgi almak istiyorum.') }}"
        target="_blank"
        rel="noopener"
    >
        <x-ay-icon name="whatsapp" :filled="true" />
        <span>WhatsApp'tan yaz</span>
    </a>

    {{-- Hızlı bakış penceresi — içeriği karttaki göz düğmesi doldurur --}}
    <dialog class="quick-dialog" id="hizli-bakis" aria-label="Hızlı bakış">
        <button type="button" class="quick-dialog__close" data-quick-close aria-label="Kapat">
            <x-ay-icon name="close" />
        </button>
        <div class="quick-dialog__body" data-quick-body>
            <div class="quick-dialog__loading"><span class="spinner"></span></div>
        </div>
    </dialog>

    @stack('scripts')
</body>
</html>
