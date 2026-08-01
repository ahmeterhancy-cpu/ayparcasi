@php $cutoff = (int) setting('same_day_cutoff_hour', 15); @endphp

<x-mail.shell :preview="'Sipariş no '.$order->number.' — '.money($order->total)">
    <h1 class="h1">Siparişiniz alındı</h1>

    <p class="p">
        Merhaba {{ explode(' ', trim($order->customer_name))[0] }},
        <strong>{{ $order->number }}</strong> numaralı siparişinizi aldık.
        @if ($order->payment_method === 'tiko' && $order->payment_status === 'paid')
            Ödemeniz alındı, hazırlığa başlıyoruz.
        @elseif ($order->payment_method === 'whatsapp')
            Detayları WhatsApp'tan konuşup onaylayacağız.
        @elseif ($order->payment_method === 'transfer')
            Havale ulaştığında hazırlığa başlıyoruz.
        @else
            Kısa süre içinde onaylayıp hazırlığa başlıyoruz.
        @endif
    </p>

    <p style="margin:22px 0 6px">
        <a class="btn" href="{{ $orderUrl }}">Siparişimi görüntüle</a>
    </p>

    <h2 class="h2" style="margin-top:28px">Sipariş özeti</h2>
    @include('emails.partials.order-summary', ['order' => $order])

    <h2 class="h2" style="margin-top:26px">Teslimat</h2>
    @include('emails.partials.delivery-facts', ['order' => $order])

    @if ($order->payment_method === 'transfer' && setting('bank_details'))
        <h2 class="h2" style="margin-top:26px">Havale bilgileri</h2>
        <p class="p" style="white-space:pre-line">{{ setting('bank_details') }}</p>
        <p class="p small">
            Açıklamaya sipariş numaranızı (<strong>{{ $order->number }}</strong>) yazmayı unutmayın.
        </p>
    @endif

    <p class="p small" style="margin-top:24px">
        Buketiniz siparişinizden sonra dükkânımızda elde hazırlanır.
        Değiştirmek istediğiniz bir şey varsa saat {{ sprintf('%02d:00', $cutoff) }}'den önce
        <a href="{{ wa_link('Merhaba, '.$order->number.' numaralı siparişim hakkında…') }}">WhatsApp'tan</a> yazın.
    </p>
</x-mail.shell>
