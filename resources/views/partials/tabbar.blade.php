{{-- Mobil alt gezinme çubuğu.
     Yalnızca dar ekranlarda görünür; başlıktaki arama ve menü katmanlarının
     aynısını açar, yeni bir katman kurmaz. --}}
@php
    $isShop = request()->routeIs('shop.*');
    $isAccount = request()->routeIs('account.*') || request()->routeIs('login') || request()->routeIs('register');
    $isCart = request()->routeIs('cart.*') || request()->routeIs('checkout.*');
@endphp

<nav class="tabbar" aria-label="Hızlı gezinme">
    <a class="tabbar__item {{ $isShop ? 'is-on' : '' }}" href="{{ route('shop.index') }}">
        <x-ay-icon name="store" />
        <span>Mağaza</span>
    </a>

    <a class="tabbar__item {{ $isAccount ? 'is-on' : '' }}"
       href="{{ auth()->check() ? route('account.index') : route('login') }}">
        <x-ay-icon name="user" />
        <span>{{ auth()->check() ? 'Hesabım' : 'Giriş' }}</span>
    </a>

    <a class="tabbar__item {{ $isCart ? 'is-on' : '' }}" href="{{ route('cart.index') }}">
        <span class="tabbar__icon">
            <x-ay-icon name="cart" />
            @if ($cartCount > 0)
                <span class="tabbar__count">{{ $cartCount }}</span>
            @endif
        </span>
        <span>Sepet</span>
    </a>

    <button class="tabbar__item" type="button" data-toggle="#arama" aria-expanded="false" aria-controls="arama">
        <x-ay-icon name="search" />
        <span>Ara</span>
    </button>

    <button class="tabbar__item" type="button" data-toggle="#menu" aria-expanded="false" aria-controls="menu">
        <x-ay-icon name="grid" />
        <span>Kategoriler</span>
    </button>
</nav>
