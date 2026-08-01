<x-print.layout :title="'Teslim fişi '.$order->number">
    <div class="sheet">
        <div class="head">
            <div>
                <div class="brand">{{ setting('shop_name', 'Ay Parçası') }}</div>
                <div class="muted">{{ setting('phone') }}</div>
            </div>
            <div style="text-align:right">
                <h1>Teslim Fişi</h1>
                <div><strong>{{ $order->number }}</strong></div>
            </div>
        </div>

        {{-- Kurye bunlara bakacak: iri ve net --}}
        <div style="margin-top:18px">
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;color:#666">Alıcı</div>
            <div style="font-size:26px;font-weight:700;line-height:1.15">{{ $order->recipient_name }}</div>

            @if ($order->recipient_phone)
                <div style="font-size:22px;font-weight:700;margin-top:4px">{{ $order->recipient_phone }}</div>
            @else
                <div class="muted" style="margin-top:4px">Alıcı telefonu yok — sipariş vereni ara: {{ $order->customer_phone }}</div>
            @endif
        </div>

        <div style="margin-top:16px;padding:12px;border:2px solid #111;border-radius:6px">
            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;color:#666">Adres</div>
            <div style="font-size:19px;font-weight:600;line-height:1.35;margin-top:2px">
                {{ $order->delivery_address }}
            </div>
            <div style="font-size:15px;margin-top:6px">
                <strong>{{ $order->delivery_zone_name }}</strong>
                ·
                {{ $order->delivery_date?->translatedFormat('d F Y') }}
                @if ($order->delivery_slot)
                    · <strong>{{ $order->delivery_slot }}</strong>
                @endif
            </div>
        </div>

        @if ($order->card_message)
            <div style="margin-top:16px;padding:14px;border:1px dashed #999;border-radius:6px">
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:.1em;color:#666">Kart notu — el yazısıyla yazılacak</div>
                <div style="font-size:20px;line-height:1.45;margin-top:6px;font-style:italic">
                    “{{ $order->card_message }}”
                </div>
                <div style="margin-top:8px;font-size:14px">
                    @if ($order->hide_sender)
                        <strong>Gönderen adı YAZILMAYACAK</strong> (müşteri gizli olmasını istedi)
                    @elseif ($order->card_sender)
                        Gönderen: <strong>{{ $order->card_sender }}</strong>
                    @else
                        Gönderen: <span class="muted">belirtilmemiş</span>
                    @endif
                </div>
            </div>
        @endif

        <h2>Hazırlanacak</h2>
        <table>
            @foreach ($order->items as $item)
                <tr>
                    <td style="font-size:15px">
                        <strong>{{ $item->quantity }} ×</strong> {{ $item->name }}
                        @if ($item->variant_name)
                            <span class="muted">({{ $item->variant_name }})</span>
                        @endif
                        @if ($item->addons)
                            <div style="font-size:13px">
                                + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>

        @if ($order->note)
            <div style="margin-top:14px;padding:10px;background:#f4f4f4;border-radius:6px">
                <strong>Müşteri notu:</strong> {{ $order->note }}
            </div>
        @endif

        <hr class="rule">

        <div style="display:flex;justify-content:space-between;font-size:15px">
            <div>
                Ödeme: <strong>{{ $order->payment_method_label }}</strong>
                @if ($order->payment_status !== 'paid')
                    — <strong style="color:#b03826">TAHSİL EDİLECEK: {{ money($order->total) }}</strong>
                @else
                    — <span class="muted">ödendi, tahsilat yok</span>
                @endif
            </div>
        </div>

        <div style="margin-top:26px;display:flex;gap:40px">
            <div style="flex:1">
                <div style="border-top:1px solid #111;padding-top:4px" class="muted">Teslim eden</div>
            </div>
            <div style="flex:1">
                <div style="border-top:1px solid #111;padding-top:4px" class="muted">Teslim alan / tarih</div>
            </div>
        </div>
    </div>
</x-print.layout>
