<x-layouts.account title="Siparişlerim" heading="Siparişlerim">

    @if ($orders->isEmpty())
        <div class="acc-empty">
            <x-ay-icon name="cart" style="width:38px;height:38px;color:var(--turq)" />
            <h3>Henüz sipariş vermediniz</h3>
            <p class="muted">Verdiğiniz her sipariş burada listelenir; durumunu buradan takip edersiniz.</p>
            <a class="btn btn--rect" href="{{ route('shop.index') }}">Ürünlere göz at</a>
        </div>
    @else
        <div class="order-list">
            @foreach ($orders as $order)
                @include('account.partials.order-row', ['order' => $order])
            @endforeach
        </div>

        {{ $orders->links() }}
    @endif
</x-layouts.account>
