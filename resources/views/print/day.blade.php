<x-print.layout title="Günün teslimatları" :auto-print="false">
    <div class="sheet">
        <div class="head">
            <div>
                <div class="brand">{{ setting('shop_name', 'Ay Parçası') }}</div>
                <div class="muted">Günün teslimat listesi</div>
            </div>
            <div style="text-align:right">
                <h1>{{ today()->translatedFormat('d F Y') }}</h1>
                <div class="muted">{{ $orders->count() }} teslimat</div>
            </div>
        </div>

        @if ($orders->isEmpty())
            <p style="margin-top:20px">Bugün için teslimat yok.</p>
        @else
            <table style="margin-top:14px">
                <thead>
                    <tr>
                        <th style="width:22mm">Saat</th>
                        <th style="width:24mm">Sipariş</th>
                        <th>Alıcı & adres</th>
                        <th style="width:40mm">Ürünler</th>
                        <th class="num" style="width:26mm">Tahsilat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr style="border-bottom:1px solid #eee">
                            <td><strong>{{ $order->delivery_slot ?: '—' }}</strong></td>

                            <td>
                                {{ $order->number }}
                                <div class="muted" style="font-size:11px">{{ $order->status_label }}</div>
                            </td>

                            <td>
                                <strong>{{ $order->recipient_name }}</strong>
                                @if ($order->recipient_phone)
                                    · {{ $order->recipient_phone }}
                                @endif
                                <div style="font-size:12px">{{ $order->delivery_zone_name }} — {{ $order->delivery_address }}</div>
                                @if ($order->card_message)
                                    <div class="muted" style="font-size:11px;font-style:italic">
                                        Kart: “{{ \Illuminate\Support\Str::limit($order->card_message, 60) }}”
                                    </div>
                                @endif
                            </td>

                            <td style="font-size:12px">
                                @foreach ($order->items as $item)
                                    {{ $item->quantity }}× {{ $item->name }}@if (! $loop->last)<br>@endif
                                @endforeach
                            </td>

                            <td class="num">
                                @if ($order->payment_status === 'paid')
                                    <span class="muted">ödendi</span>
                                @else
                                    <strong>{{ money($order->total) }}</strong>
                                    <div class="muted" style="font-size:11px">{{ $order->payment_method_label }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <hr class="rule">

            <p>
                Tahsil edilecek toplam:
                <strong>{{ money($orders->where('payment_status', '!=', 'paid')->sum('total')) }}</strong>
            </p>
        @endif
    </div>
</x-print.layout>
