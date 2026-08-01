@php($transparent = $transparent ?? false)

@if ($announcement)
    <div class="announce">
        {{ $announcement->title }}
        @if ($announcement->link)
            <a href="{{ $announcement->link }}">{{ $announcement->cta_label ?: 'Keşfet' }}</a>
        @endif
    </div>
@endif

<header class="header {{ $transparent ? 'header--over' : '' }}" data-header>
    <div class="wrap header__inner">
        <a class="brand" href="{{ route('home') }}">
            <x-logo class="brand__mark" />
            <span>
                <span class="brand__name">{{ setting('shop_name', 'Ay Parçası') }}</span>
                <span class="brand__tag">{{ setting('tagline', 'Hediyelik Tasarımlar & Çiçekçi') }}</span>
            </span>
        </a>

        <nav class="nav nav--main" aria-label="Ana menü">
            <div class="nav__item">
                <a class="nav__link" href="{{ route('shop.index') }}">Tüm Ürünler</a>
            </div>

            @foreach ($navCategories->take(4) as $cat)
                <div class="nav__item">
                    <a class="nav__link" href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a>

                    @if ($cat->children->isNotEmpty())
                        <div class="nav__menu">
                            @foreach ($cat->children as $child)
                                <a href="{{ route('shop.category', $child->slug) }}">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="nav__item"><a class="nav__link" href="{{ route('page.delivery') }}">Teslimat</a></div>
            <div class="nav__item"><a class="nav__link" href="{{ route('page.about') }}">Hakkımızda</a></div>
        </nav>

        <div class="actions">
            <button class="icon-btn" type="button" data-toggle="#arama" aria-expanded="false" aria-label="Ara">
                <x-ay-icon name="search" />
            </button>

            <a class="icon-btn" href="{{ route('cart.index') }}" aria-label="Sepet ({{ $cartCount }} ürün)">
                <x-ay-icon name="cart" />
                @if ($cartCount > 0)
                    <span class="icon-btn__count">{{ $cartCount }}</span>
                @endif
            </a>

            <button class="icon-btn menu-btn" type="button" data-toggle="#menu" aria-expanded="false" aria-label="Menü">
                <x-ay-icon name="menu" />
            </button>
        </div>
    </div>
</header>

{{-- Arama katmanı --}}
<div class="overlay overlay--search" id="arama" role="dialog" aria-modal="true" aria-label="Ürün ara">
    <div class="overlay__scrim" data-close></div>
    <div class="overlay__panel">
        <div class="wrap">
            <form action="{{ route('shop.search') }}" method="GET" style="display:grid;gap:1rem">
                <label class="label" for="q">Ne aramıştınız?</label>
                <div style="display:flex;gap:.6rem">
                    <input
                        class="input" type="search" name="q" id="q"
                        placeholder="Orkide, gül buketi, doğum günü…"
                        value="{{ request('q') }}" autocomplete="off"
                    >
                    <button class="btn" type="submit"><x-ay-icon name="search" /> Ara</button>
                    <button class="btn btn--ghost" type="button" data-close aria-label="Kapat"><x-ay-icon name="close" /></button>
                </div>
            </form>

            <div style="display:flex;flex-wrap:wrap;gap:.45rem;margin-top:1.25rem">
                @foreach ($navCategories->take(6) as $cat)
                    <a class="chip" href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Mobil menü --}}
<div class="overlay" id="menu" role="dialog" aria-modal="true" aria-label="Menü">
    <div class="overlay__scrim" data-close></div>
    <div class="overlay__panel">
        <div class="overlay__head">
            <span class="brand__name">Menü</span>
            <button class="icon-btn" type="button" data-close aria-label="Kapat"><x-ay-icon name="close" /></button>
        </div>

        <div class="overlay__body">
            <ul class="mobile-nav">
                <li><a href="{{ route('shop.index') }}">Tüm Ürünler</a></li>

                @foreach ($navCategories as $cat)
                    <li>
                        <a href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a>
                        @if ($cat->children->isNotEmpty())
                            <div class="mobile-nav__sub">
                                @foreach ($cat->children as $child)
                                    <a href="{{ route('shop.category', $child->slug) }}">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach

                <li><a href="{{ route('page.delivery') }}">Teslimat</a></li>
                <li><a href="{{ route('page.about') }}">Hakkımızda</a></li>
                <li><a href="{{ route('page.blog') }}">Günlük</a></li>
                <li><a href="{{ route('page.contact') }}">İletişim</a></li>
            </ul>

            <a class="btn btn--wa btn--block" style="margin-top:1.5rem"
               href="{{ wa_link('Merhaba, sipariş vermek istiyorum.') }}" target="_blank" rel="noopener">
                <x-ay-icon name="whatsapp" :filled="true" /> WhatsApp ile sipariş
            </a>
        </div>
    </div>
</div>
