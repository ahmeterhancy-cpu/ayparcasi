@php
    $images = $product->images;
    if (empty($images)) {
        $images = ['https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=900&q=70'];
    }
    $variants = $product->variants->where('is_active', true)->values();
    $default = $variants->firstWhere('is_default', true) ?? $variants->sortBy('price')->first();
    $ship = ship_countdown();
@endphp

<div class="quick">
    <div class="quick__media">
        <img src="{{ img_url($images[0]) }}" alt="{{ $product->name }}" decoding="async">
        @if ($product->discount_percent)
            <span class="card__badge">%{{ $product->discount_percent }}</span>
        @endif
    </div>

    <div class="quick__info">
        @if ($cat = $product->categories->first())
            <a class="card__cat" href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a>
        @endif

        <h2 class="quick__title">{{ $product->name }}</h2>

        @if ($product->short_description)
            <p class="muted" style="font-size:.92rem">{{ $product->short_description }}</p>
        @endif

        <p class="card__price">
            <strong data-price-out>{{ money($product->display_price) }}</strong>
            <del data-compare-out @if (! $product->display_compare_at) hidden @endif>
                {{ $product->display_compare_at ? money($product->display_compare_at) : '' }}
            </del>
        </p>

        <span class="stock" data-stock-out data-state="{{ $product->stock_state }}">{{ $product->stock_label }}</span>

        @if ($product->is_orderable)
            <form method="POST" action="{{ route('cart.store') }}"
                  data-product-form
                  data-base-price="{{ (float) $product->price }}"
                  data-base-compare="{{ (float) ($product->compare_at_price ?? 0) }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if ($variants->isNotEmpty())
                    <div class="sizes">
                        <span class="label">Boy seçin</span>
                        @foreach ($variants as $variant)
                            @php $orderable = ! $product->track_stock || $variant->stock > 0; @endphp
                            <label class="size">
                                <input type="radio" name="variant_id" value="{{ $variant->id }}"
                                       data-price="{{ (float) $variant->price }}"
                                       data-compare="{{ (float) ($variant->compare_at_price ?? 0) }}"
                                       data-name="{{ $variant->name }}"
                                       data-stock-state="{{ $orderable ? ($variant->stock <= 3 && $product->track_stock ? 'low' : 'in_stock') : 'out_of_stock' }}"
                                       data-stock-label="{{ $orderable ? ($variant->stock <= 3 && $product->track_stock ? 'Son '.$variant->stock.' adet' : 'Stokta') : 'Tükendi' }}"
                                       @checked($default && $variant->id === $default->id)
                                       @disabled(! $orderable)>
                                <span>
                                    <span class="size__name">{{ $variant->name }}</span>
                                    @if ($variant->description)
                                        <span class="size__desc"> · {{ $variant->description }}</span>
                                    @endif
                                </span>
                                <span class="size__price">{{ money($variant->price) }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="quick__buy">
                    <div class="qty">
                        <button type="button" data-qty-step="-1" aria-label="Azalt"><x-ay-icon name="minus" /></button>
                        <label class="sr-only" for="quick-adet">Adet</label>
                        <input type="number" name="quantity" id="quick-adet" value="1" min="1" max="99" inputmode="numeric">
                        <button type="button" data-qty-step="1" aria-label="Artır"><x-ay-icon name="plus" /></button>
                    </div>

                    <button class="btn btn--rect" type="submit" style="flex:1">
                        <x-ay-icon name="cart" /> Sepete Ekle
                    </button>
                </div>
            </form>
        @else
            <p style="font-weight:600">Bu tasarım şu an tükendi.</p>
        @endif

        <p class="card__ship" data-ship data-ship-open="{{ $ship['open'] ? '1' : '0' }}">
            <span class="card__ship-open">Bugün teslim için <strong data-ship-out>{{ $ship['label'] }}</strong></span>
            <span class="card__ship-closed">Yarın teslim edilir</span>
        </p>

        <div class="quick__foot">
            <button type="button" class="btn btn--rect btn--wa btn--sm"
                    data-stock-ask="{{ route('inquiry.stock') }}"
                    data-product-id="{{ $product->id }}"
                    data-source="product"
                    data-fallback="{{ wa_link('Merhaba, "'.$product->name.'" ürününün stok durumunu öğrenebilir miyim? '.$product->url) }}">
                <x-ay-icon name="whatsapp" :filled="true" /> Stok sor
            </button>

            <a class="link-u" href="{{ $product->url }}">Tüm detaylar <x-ay-icon name="arrow-right" /></a>
        </div>
    </div>
</div>
