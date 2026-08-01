<x-layouts.account
    title="Hesabım"
    :heading="'Merhaba, '.auth()->user()->first_name"
    lead="Siparişlerinizin durumunu buradan takip edebilir, adres ve favorilerinizi yönetebilirsiniz.">

    @if ($openOrder)
        <section class="acc-panel acc-panel--live">
            <div>
                <span class="eyebrow">Devam eden sipariş</span>
                <h2 style="margin-block:.5rem .35rem;font-size:1.35rem">{{ $openOrder->number }}</h2>
                <p class="muted" style="font-size:.92rem">
                    {{ $openOrder->recipient_name }} · {{ $openOrder->delivery_zone_name }} ·
                    {{ $openOrder->delivery_date?->translatedFormat('d F Y') }}
                    @if ($openOrder->delivery_slot) / {{ $openOrder->delivery_slot }} @endif
                </p>
            </div>

            <div class="acc-panel__side">
                <span class="order-status" data-status="{{ $openOrder->status }}">{{ $openOrder->status_label }}</span>
                <a class="btn btn--rect btn--sm" href="{{ route('account.order', $openOrder->number) }}">Siparişi aç</a>
            </div>
        </section>
    @endif

    <div class="acc-stats">
        <a class="acc-stat" href="{{ route('account.orders') }}">
            <span class="acc-stat__num">{{ $orderCount }}</span>
            <span class="acc-stat__label">Sipariş</span>
        </a>
        <a class="acc-stat" href="{{ route('account.favorites') }}">
            <span class="acc-stat__num">{{ $favoriteCount }}</span>
            <span class="acc-stat__label">Favori</span>
        </a>
        <a class="acc-stat" href="{{ route('account.addresses') }}">
            <span class="acc-stat__num">{{ $addressCount }}</span>
            <span class="acc-stat__label">Kayıtlı adres</span>
        </a>
    </div>

    <section>
        <div class="acc-head">
            <h2>Son siparişleriniz</h2>
            @if ($orderCount > 3)
                <a class="link-u" href="{{ route('account.orders') }}">Tümünü gör <x-ay-icon name="arrow-right" /></a>
            @endif
        </div>

        @if ($orders->isEmpty())
            <div class="acc-empty">
                <x-ay-icon name="flower" style="width:38px;height:38px;color:var(--turq)" />
                <h3>Henüz sipariş vermediniz</h3>
                <p class="muted">İlk buketinizi seçelim mi?</p>
                <a class="btn btn--rect" href="{{ route('shop.index') }}">Ürünlere göz at</a>
            </div>
        @else
            <div class="order-list">
                @foreach ($orders as $order)
                    @include('account.partials.order-row', ['order' => $order])
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.account>
