@php
    $flow = ['pending' => 'Alındı', 'confirmed' => 'Onaylandı', 'preparing' => 'Hazırlanıyor', 'on_the_way' => 'Yolda', 'delivered' => 'Teslim edildi'];
    $keys = array_keys($flow);
    $at = array_search($order->status, $keys, true);
    $cancelled = $order->status === 'cancelled';
@endphp

<x-layouts.account :title="'Sipariş '.$order->number" :heading="'Sipariş '.$order->number">

    <p class="muted" style="margin-top:-1rem">
        {{ $order->created_at?->translatedFormat('d F Y, H:i') }} tarihinde oluşturuldu
    </p>

    {{-- Durum çizgisi --}}
    @if ($cancelled)
        <div class="alert" style="margin-top:1.5rem">Bu sipariş iptal edildi.</div>
    @else
        <ol class="order-flow" style="margin-top:1.5rem">
            @foreach ($flow as $key => $label)
                @php $done = $at !== false && array_search($key, $keys, true) <= $at; @endphp
                <li class="{{ $done ? 'is-done' : '' }}{{ $key === $order->status ? ' is-now' : '' }}">
                    <span class="order-flow__dot" aria-hidden="true"></span>
                    <span class="order-flow__label">{{ $label }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="order-grid">
        <section class="acc-box">
            <h2>Ürünler</h2>

            <div class="order-items">
                @foreach ($order->items as $item)
                    <div class="order-item">
                        <img src="{{ img_url($item->image, 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=200&q=70') }}"
                             alt="" loading="lazy" decoding="async">
                        <div>
                            <strong>{{ $item->name }}</strong>
                            @if ($item->variant_name)
                                <span class="muted"> ({{ $item->variant_name }})</span>
                            @endif
                            @if ($item->addons)
                                <p class="muted" style="font-size:.85rem">
                                    + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                                </p>
                            @endif
                            <p class="muted" style="font-size:.85rem">{{ $item->quantity }} adet × {{ money($item->unit_price) }}</p>
                        </div>
                        <strong style="white-space:nowrap">{{ money($item->line_total) }}</strong>
                    </div>
                @endforeach
            </div>

            <dl class="order-totals">
                <div><dt>Ara toplam</dt><dd>{{ money($order->subtotal) }}</dd></div>
                @if ((float) $order->discount > 0)
                    <div><dt>İndirim{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt>
                        <dd style="color:var(--ok)">-{{ money($order->discount) }}</dd></div>
                @endif
                <div><dt>Teslimat</dt>
                    <dd>{{ (float) $order->delivery_fee > 0 ? money($order->delivery_fee) : 'Ücretsiz' }}</dd></div>
                <div class="order-totals__sum"><dt>Toplam</dt><dd>{{ money($order->total) }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('account.order.reorder', $order->number) }}" style="margin-top:1.25rem">
                @csrf
                <button class="btn btn--rect btn--ghost btn--block" type="submit">
                    <x-ay-icon name="cart" /> Aynı siparişi tekrarla
                </button>
            </form>
        </section>

        <aside class="acc-box">
            <h2>Teslimat</h2>

            <dl class="order-facts">
                <div><dt>Alıcı</dt><dd>{{ $order->recipient_name }}</dd></div>
                @if ($order->recipient_phone)
                    <div><dt>Alıcı telefonu</dt><dd>{{ $order->recipient_phone }}</dd></div>
                @endif
                <div><dt>Bölge</dt><dd>{{ $order->delivery_zone_name }}</dd></div>
                <div><dt>Adres</dt><dd>{{ $order->delivery_address }}</dd></div>
                <div>
                    <dt>Tarih</dt>
                    <dd>
                        {{ $order->delivery_date?->translatedFormat('d F Y') }}
                        @if ($order->delivery_slot) · {{ $order->delivery_slot }} @endif
                    </dd>
                </div>
                @if ($order->card_message)
                    <div><dt>Kart notu</dt><dd>{{ $order->card_message }}</dd></div>
                @endif
                <div><dt>Ödeme</dt>
                    <dd>{{ $order->payment_method_label }} —
                        {{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}</dd></div>
            </dl>

            <a class="btn btn--rect btn--wa btn--block" style="margin-top:1.25rem"
               href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                <x-ay-icon name="whatsapp" :filled="true" /> Sipariş hakkında yaz
            </a>
        </aside>
    </div>

    <p style="margin-top:1.75rem">
        <a class="link-u" href="{{ route('account.orders') }}">Siparişlerime dön</a>
    </p>
</x-layouts.account>
