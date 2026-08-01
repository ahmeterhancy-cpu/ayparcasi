<article class="order-row">
    <div class="order-row__thumbs">
        @foreach ($order->items->take(3) as $item)
            <img src="{{ img_url($item->image, 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=200&q=70') }}"
                 alt="" loading="lazy" decoding="async">
        @endforeach
        @if ($order->items->count() > 3)
            <span class="order-row__more">+{{ $order->items->count() - 3 }}</span>
        @endif
    </div>

    <div class="order-row__body">
        <p class="order-row__meta">
            {{ $order->created_at?->translatedFormat('d F Y') }} · {{ $order->items->sum('quantity') }} ürün
        </p>
        <h3 class="order-row__no">
            <a href="{{ route('account.order', $order->number) }}">{{ $order->number }}</a>
        </h3>
        <p class="order-row__meta">
            {{ $order->recipient_name }} · {{ $order->delivery_zone_name }}
        </p>
    </div>

    <div class="order-row__side">
        <span class="order-status" data-status="{{ $order->status }}">{{ $order->status_label }}</span>
        <strong class="order-row__total">{{ money($order->total) }}</strong>
        @if ($order->payment_status !== 'paid' && $order->status !== 'cancelled')
            <span class="order-row__unpaid">{{ $order->payment_method_label }} · ödeme bekliyor</span>
        @endif
    </div>
</article>
