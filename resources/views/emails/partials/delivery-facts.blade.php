{{-- Teslimat bilgileri --}}
<table role="presentation" width="100%" style="margin:6px 0 4px">
    <tr class="row">
        <td class="k">Alıcı</td>
        <td>{{ $order->recipient_name }}@if ($order->recipient_phone) · {{ $order->recipient_phone }}@endif</td>
    </tr>
    <tr class="row">
        <td class="k">Adres</td>
        <td>{{ $order->delivery_zone_name ? $order->delivery_zone_name.' — ' : '' }}{{ $order->delivery_address }}</td>
    </tr>
    <tr class="row">
        <td class="k">Teslim tarihi</td>
        <td>
            {{ $order->delivery_date?->translatedFormat('d F Y') ?: 'Belirtilmedi' }}
            @if ($order->delivery_slot) · {{ $order->delivery_slot }} @endif
        </td>
    </tr>
    @if ($order->card_message)
        <tr class="row">
            <td class="k">Kart notu</td>
            <td style="font-style:italic">“{{ $order->card_message }}”</td>
        </tr>
    @endif
    <tr class="row">
        <td class="k">Ödeme</td>
        <td>
            {{ $order->payment_method_label }} —
            {{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}
        </td>
    </tr>
</table>
