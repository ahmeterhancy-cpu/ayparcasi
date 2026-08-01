<x-layouts.account title="Favorilerim" heading="Favorilerim"
                   lead="Beğendiğiniz tasarımlar burada bekler; stok durumu ve fiyat güncel görünür.">

    @if ($products->isEmpty())
        <div class="acc-empty">
            <x-ay-icon name="heart" style="width:38px;height:38px;color:var(--turq)" />
            <h3>Favori listeniz boş</h3>
            <p class="muted">Beğendiğiniz ürünlerin köşesindeki kalbe dokunun, buraya eklensin.</p>
            <a class="btn btn--rect" href="{{ route('shop.index') }}">Ürünlere göz at</a>
        </div>
    @else
        <div class="grid-products">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    @endif
</x-layouts.account>
