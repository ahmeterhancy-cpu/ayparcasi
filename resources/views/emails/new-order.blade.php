<x-mail.shell :preview="$order->number.' · '.money($order->total).' · '.$order->delivery_zone_name" accent="#db4a32">
    <h1 class="h1">Yeni sipariş: {{ $order->number }}</h1>

    <p class="p">
        {{ $order->created_at?->translatedFormat('d F Y, H:i') }} ·
        <strong>{{ money($order->total) }}</strong> ·
        {{ $order->payment_method_label }}
        @if ($order->payment_status !== 'paid')
            <span style="color:#b4701a">(tahsil edilecek)</span>
        @endif
    </p>

    <p style="margin:20px 0 6px">
        <a class="btn" href="{{ $adminUrl }}">Panelde aç</a>
        &nbsp;
        <a href="{{ $slipUrl }}" style="font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px">Teslim fişini yazdır</a>
    </p>

    <h2 class="h2" style="margin-top:26px">Sipariş veren</h2>
    <table role="presentation" width="100%">
        <tr class="row"><td class="k">Ad soyad</td><td>{{ $order->customer_name }}</td></tr>
        <tr class="row"><td class="k">Telefon</td><td>{{ $order->customer_phone }}</td></tr>
        @if ($order->customer_email)
            <tr class="row"><td class="k">E-posta</td><td>{{ $order->customer_email }}</td></tr>
        @endif
    </table>

    <h2 class="h2" style="margin-top:26px">Teslimat</h2>
    @include('emails.partials.delivery-facts', ['order' => $order])

    <h2 class="h2" style="margin-top:26px">Hazırlanacak</h2>
    @include('emails.partials.order-summary', ['order' => $order])

    @if ($order->note)
        <p class="p" style="margin-top:18px;padding:12px;background:#efe4d3;border-radius:8px">
            <strong>Müşteri notu:</strong> {{ $order->note }}
        </p>
    @endif
</x-mail.shell>
