<x-layouts.app :title="'Sipariş '.$order->number">

    <div class="wrap receipt" @if ($openWhatsapp) data-auto-whatsapp="{{ $whatsappUrl }}" @endif>

        <div class="receipt__hero">
            <span class="receipt__mark"><x-ay-icon name="check" /></span>
            <h1>Siparişiniz alındı</h1>
            <p class="lead">
                Sipariş numaranız <strong>{{ $order->number }}</strong>.
                @if ($order->payment_method === 'whatsapp')
                    WhatsApp penceresini açtık — detayları oradan konuşalım.
                @elseif ($order->payment_status === 'paid')
                    Ödemeniz alındı, hazırlığa başlıyoruz.
                @else
                    Sizi kısa süre içinde arayıp onaylayacağız.
                @endif
            </p>

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;justify-content:center;margin-top:.5rem">
                <a class="btn btn--wa" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                    <x-ay-icon name="whatsapp" :filled="true" /> Siparişi WhatsApp'tan aç
                </a>
                <a class="btn btn--ghost" href="{{ route('shop.index') }}">Alışverişe devam et</a>
            </div>
        </div>

        <div class="receipt__box">
            <h2 style="font-size:1.05rem">Teslimat</h2>

            <dl style="display:grid;gap:.2rem">
                <div class="receipt__row"><dt>Alıcı</dt><dd>{{ $order->recipient_name }}</dd></div>
                @if ($order->recipient_phone)
                    <div class="receipt__row"><dt>Alıcı telefonu</dt><dd>{{ $order->recipient_phone }}</dd></div>
                @endif
                <div class="receipt__row"><dt>Bölge</dt><dd>{{ $order->delivery_zone_name }}</dd></div>
                <div class="receipt__row"><dt>Adres</dt><dd style="max-width:22rem">{{ $order->delivery_address }}</dd></div>
                <div class="receipt__row">
                    <dt>Tarih</dt>
                    <dd>
                        {{ $order->delivery_date?->translatedFormat('d F Y') }}
                        @if ($order->delivery_slot) · {{ $order->delivery_slot }} @endif
                    </dd>
                </div>
                @if ($order->card_message)
                    <div class="receipt__row"><dt>Kart notu</dt><dd style="max-width:22rem">{{ $order->card_message }}</dd></div>
                @endif
                <div class="receipt__row"><dt>Ödeme</dt><dd>{{ $order->payment_method_label }}</dd></div>
                <div class="receipt__row"><dt>Durum</dt><dd>{{ $order->status_label }}</dd></div>
            </dl>

            <div class="receipt__items">
                @foreach ($order->items as $item)
                    <div class="receipt__item">
                        <span>
                            <strong>{{ $item->quantity }}×</strong> {{ $item->name }}
                            @if ($item->variant_name)
                                <span class="muted">({{ $item->variant_name }})</span>
                            @endif
                            @if ($item->addons)
                                <br><span class="muted" style="font-size:.85em">
                                    + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                                </span>
                            @endif
                        </span>
                        <span style="font-weight:600;white-space:nowrap">{{ money($item->line_total) }}</span>
                    </div>
                @endforeach
            </div>

            <div style="border-top:1px solid var(--line);padding-top:1rem;display:grid;gap:.4rem">
                <div class="receipt__row"><dt>Ara toplam</dt><dd>{{ money($order->subtotal) }}</dd></div>
                @if ((float) $order->discount > 0)
                    <div class="receipt__row"><dt>İndirim{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt>
                        <dd style="color:var(--ok)">-{{ money($order->discount) }}</dd></div>
                @endif
                <div class="receipt__row"><dt>Teslimat</dt>
                    <dd>{{ (float) $order->delivery_fee > 0 ? money($order->delivery_fee) : 'Ücretsiz' }}</dd></div>
                <div class="receipt__row" style="font-size:1.15rem;font-family:var(--font-display);padding-top:.5rem">
                    <dt style="color:var(--ink)">Toplam</dt><dd>{{ money($order->total) }}</dd>
                </div>
            </div>
        </div>

        @if ($order->payment_method === 'transfer')
            <div class="receipt__box">
                <h2 style="font-size:1.05rem">Havale bilgileri</h2>
                <p style="font-size:.92rem;color:var(--ink-2)">
                    {!! nl2br(e(setting('bank_details', 'Hesap bilgilerini WhatsApp\'tan paylaşacağız.'))) !!}
                </p>
                <p class="muted" style="font-size:.85rem">
                    Açıklamaya sipariş numaranızı ({{ $order->number }}) yazmayı unutmayın.
                </p>
            </div>
        @endif

        <p class="muted" style="text-align:center;font-size:.88rem">
            Bu sayfayı kaydedin — siparişinizin durumunu buradan takip edebilirsiniz.
        </p>
    </div>

</x-layouts.app>
