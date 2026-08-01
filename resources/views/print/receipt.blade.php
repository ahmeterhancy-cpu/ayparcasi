<x-print.layout :title="'Sipariş fişi '.$order->number">
    <div class="sheet">
        <div class="head">
            <div>
                <div class="brand">{{ setting('shop_name', 'Ay Parçası') }}</div>
                <div class="muted">{{ setting('tagline') }}</div>
                <div class="muted" style="margin-top:6px">
                    {{ setting('address') }}<br>
                    {{ setting('phone') }} · {{ setting('email') }}
                </div>
            </div>

            <div style="text-align:right">
                <h1>Sipariş Fişi</h1>
                <div><strong>{{ $order->number }}</strong></div>
                <div class="muted">{{ $order->created_at?->translatedFormat('d F Y, H:i') }}</div>
            </div>
        </div>

        <div style="display:flex;gap:32px;margin-top:14px">
            <div style="flex:1">
                <h2>Sipariş veren</h2>
                <dl class="facts">
                    <dt>Ad soyad</dt><dd>{{ $order->customer_name }}</dd>
                    <dt>Telefon</dt><dd>{{ $order->customer_phone }}</dd>
                    @if ($order->customer_email)
                        <dt>E-posta</dt><dd>{{ $order->customer_email }}</dd>
                    @endif
                </dl>
            </div>

            <div style="flex:1">
                <h2>Teslimat</h2>
                <dl class="facts">
                    <dt>Alıcı</dt><dd>{{ $order->recipient_name }}</dd>
                    @if ($order->recipient_phone)
                        <dt>Telefon</dt><dd>{{ $order->recipient_phone }}</dd>
                    @endif
                    <dt>Bölge</dt><dd>{{ $order->delivery_zone_name }}</dd>
                    <dt>Tarih</dt>
                    <dd>
                        {{ $order->delivery_date?->translatedFormat('d F Y') }}
                        @if ($order->delivery_slot) · {{ $order->delivery_slot }} @endif
                    </dd>
                </dl>
            </div>
        </div>

        <h2>Ürünler</h2>
        <table>
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th class="num">Adet</th>
                    <th class="num">Birim</th>
                    <th class="num">Tutar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if ($item->variant_name)
                                <span class="muted">({{ $item->variant_name }})</span>
                            @endif
                            @if ($item->addons)
                                <div class="muted" style="font-size:11px">
                                    + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                                </div>
                            @endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ money($item->unit_price) }}</td>
                        <td class="num">{{ money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="rule">

        <table style="max-width:70mm;margin-left:auto">
            <tr><td>Ara toplam</td><td class="num">{{ money($order->subtotal) }}</td></tr>

            @if ((float) $order->discount > 0)
                <tr>
                    <td>İndirim @if ($order->coupon_code) ({{ $order->coupon_code }}) @endif</td>
                    <td class="num">-{{ money($order->discount) }}</td>
                </tr>
            @endif

            <tr>
                <td>Teslimat</td>
                <td class="num">{{ (float) $order->delivery_fee > 0 ? money($order->delivery_fee) : 'Ücretsiz' }}</td>
            </tr>

            <tr class="total-row"><td>Toplam</td><td class="num">{{ money($order->total) }}</td></tr>

            @if ((float) $order->refunded_total > 0)
                <tr><td>İade edilen</td><td class="num">-{{ money($order->refunded_total) }}</td></tr>
                <tr><td><strong>Kalan</strong></td><td class="num"><strong>{{ money($order->refundable) }}</strong></td></tr>
            @endif
        </table>

        <hr class="rule">

        <dl class="facts" style="grid-template-columns:auto auto;justify-content:start">
            <dt>Ödeme yöntemi</dt><dd>{{ $order->payment_method_label }}</dd>
            <dt>Ödeme durumu</dt><dd>{{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}</dd>
            <dt>Sipariş durumu</dt><dd>{{ $order->status_label }}</dd>
        </dl>

        <p class="muted" style="margin-top:18px;font-size:11px">
            Bu belge bir sipariş fişidir, mali belge yerine geçmez.
            Teşekkür ederiz — {{ setting('shop_name', 'Ay Parçası') }}
        </p>
    </div>
</x-print.layout>
