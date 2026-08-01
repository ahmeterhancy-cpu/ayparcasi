@props(['title' => 'Hesabım', 'heading' => null, 'lead' => null])

@php
    $user = auth()->user();

    $nav = [
        ['route' => 'account.index', 'label' => 'Genel bakış', 'icon' => 'sparkle'],
        ['route' => 'account.orders', 'label' => 'Siparişlerim', 'icon' => 'cart'],
        ['route' => 'account.favorites', 'label' => 'Favorilerim', 'icon' => 'heart'],
        ['route' => 'account.addresses', 'label' => 'Adreslerim', 'icon' => 'pin'],
        ['route' => 'account.profile', 'label' => 'Bilgilerim', 'icon' => 'shield'],
    ];
@endphp

<x-layouts.app :title="$title">
    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Hesabım</span>
            <h1 data-reveal="up">{{ $heading ?? $title }}</h1>
            @if ($lead)
                <p class="lead">{{ $lead }}</p>
            @endif
        </div>
    </header>

    <div class="wrap account">
        <aside class="account__side">
            <div class="account__card">
                <span class="account__avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <span class="account__who">
                    <strong>{{ $user->name }}</strong>
                    <span class="muted">{{ $user->email }}</span>
                </span>
            </div>

            <nav class="account__nav" aria-label="Hesap menüsü">
                @foreach ($nav as $item)
                    @php $on = request()->routeIs($item['route']) || ($item['route'] === 'account.orders' && request()->routeIs('account.order')); @endphp
                    <a href="{{ route($item['route']) }}" class="{{ $on ? 'is-on' : '' }}" @if ($on) aria-current="page" @endif>
                        <x-ay-icon :name="$item['icon']" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account__logout">Çıkış yap</button>
            </form>
        </aside>

        <div class="account__main">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
