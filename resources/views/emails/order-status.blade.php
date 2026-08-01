<x-mail.shell :preview="$headline" :accent="$accent">
    <h1 class="h1">{{ $headline }}</h1>

    <p class="p">
        Merhaba {{ explode(' ', trim($order->customer_name))[0] }},
        <strong>{{ $order->number }}</strong> numaralı siparişiniz için {{ $body }}
    </p>

    <p style="margin:22px 0 6px">
        <a class="btn" href="{{ $orderUrl }}">Siparişimi görüntüle</a>
    </p>

    <h2 class="h2" style="margin-top:28px">Teslimat</h2>
    @include('emails.partials.delivery-facts', ['order' => $order])

    @if ($order->status === 'cancelled')
        <p class="p small" style="margin-top:20px">
            Ödeme almışsak iadesi birkaç iş günü içinde kartınıza yansır.
            Sorunuz olursa <a href="{{ wa_link('Merhaba, '.$order->number.' numaralı siparişim iptal edilmiş.') }}">WhatsApp'tan</a> yazabilirsiniz.
        </p>
    @elseif ($order->status === 'delivered')
        <p class="p small" style="margin-top:20px">
            Çiçeklerle ilgili bir sorun olursa 24 saat içinde bize yazın, yenisini gönderelim.
        </p>
    @endif
</x-mail.shell>
