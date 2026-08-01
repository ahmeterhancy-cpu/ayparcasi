{{-- Sipariş kalemleri ve tutarlar — birden çok e-postada kullanılır --}}
<table role="presentation" width="100%" style="margin:18px 0 4px">
    @foreach ($order->items as $item)
        <tr class="row">
            <td>
                <strong style="color:#0e2c34">{{ $item->name }}</strong>
                @if ($item->variant_name)
                    <span style="color:#5d7c83">({{ $item->variant_name }})</span>
                @endif
                @if ($item->addons)
                    <div style="font-size:13px;color:#5d7c83">
                        + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                    </div>
                @endif
                <div style="font-size:13px;color:#5d7c83">{{ $item->quantity }} adet × {{ money($item->unit_price) }}</div>
            </td>
            <td class="num">{{ money($item->line_total) }}</td>
        </tr>
    @endforeach

    <tr class="row">
        <td class="k">Ara toplam</td>
        <td class="num">{{ money($order->subtotal) }}</td>
    </tr>

    @if ((float) $order->discount > 0)
        <tr class="row">
            <td class="k">İndirim @if ($order->coupon_code) ({{ $order->coupon_code }}) @endif</td>
            <td class="num" style="color:#2f7d5c">-{{ money($order->discount) }}</td>
        </tr>
    @endif

    <tr class="row">
        <td class="k">Teslimat</td>
        <td class="num">{{ (float) $order->delivery_fee > 0 ? money($order->delivery_fee) : 'Ücretsiz' }}</td>
    </tr>

    <tr class="row total">
        <td>Toplam</td>
        <td class="num">{{ money($order->total) }}</td>
    </tr>

    @if ((float) $order->refunded_total > 0)
        <tr class="row">
            <td class="k">İade edilen</td>
            <td class="num" style="color:#db4a32">-{{ money($order->refunded_total) }}</td>
        </tr>
    @endif
</table>
