<x-mail.shell :preview="'Ödemeniz alındı — '.money($order->total)" accent="#2f7d5c">
    <h1 class="h1">Ödemeniz alındı</h1>

    <p class="p">
        Merhaba {{ explode(' ', trim($order->customer_name))[0] }},
        <strong>{{ $order->number }}</strong> numaralı siparişinizin
        <strong>{{ money($order->total) }}</strong> tutarındaki ödemesi alındı.
        Hazırlığa başlıyoruz.
    </p>

    <p style="margin:22px 0 6px">
        <a class="btn" href="{{ $orderUrl }}">Siparişimi görüntüle</a>
    </p>

    <h2 class="h2" style="margin-top:28px">Sipariş özeti</h2>
    @include('emails.partials.order-summary', ['order' => $order])

    <p class="p small" style="margin-top:22px">
        Bu e-postayı ödeme belgesi olarak saklayabilirsiniz.
    </p>
</x-mail.shell>
