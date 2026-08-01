@props(['product', 'reveal' => 'up'])

@php
    $images = $product->images;
    $main = img_url($images[0] ?? null, 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=800&q=70');
    $alt = isset($images[1]) ? img_url($images[1]) : null;
    $cat = $product->relationLoaded('categories') ? $product->categories->first() : $product->categories()->first();
    $discount = $product->discount_percent;
    $rating = $product->rating ? (float) $product->rating : null;
    $ship = ship_countdown();
    // Girişliyse sunucudan, misafirde tarayıcıdan (JS) işaretlenir
    $isFav = in_array($product->id, favorite_ids(), true);
@endphp

<article class="card {{ $product->is_orderable ? '' : 'is-out' }}" data-reveal="{{ $reveal }}">

    {{-- Görsel --}}
    <div class="card__media">
        <a class="card__link" href="{{ $product->url }}" tabindex="-1" aria-hidden="true">
            <img class="card__img" src="{{ $main }}" alt="" loading="lazy" decoding="async" width="640" height="640">
            @if ($alt)
                <img class="card__img card__img--alt" src="{{ $alt }}" alt="" loading="lazy" decoding="async" width="640" height="640">
            @endif
        </a>

        @if ($discount)
            <span class="card__badge">%{{ $discount }}</span>
        @endif

        @unless ($product->is_orderable)
            <span class="card__sold"><span class="badge">Tükendi</span></span>
        @endunless

        {{-- Hızlı işlemler: favori her zaman görünür, diğerleri üzerine gelince --}}
        <div class="card__tools">
            <button
                type="button"
                class="card__tool"
                data-fav="{{ $product->id }}"
                data-fav-name="{{ $product->name }}"
                aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                aria-label="{{ $isFav ? 'Favorilerden çıkar' : 'Favorilere ekle' }}"
                title="{{ $isFav ? 'Favorilerden çıkar' : 'Favorilere ekle' }}"
            >
                <x-ay-icon name="heart" />
            </button>

            <button
                type="button"
                class="card__tool card__tool--extra"
                style="--i:0"
                data-quickview="{{ route('shop.quickview', $product->slug) }}"
                aria-label="Hızlı bakış"
                title="Hızlı bakış"
            >
                <x-ay-icon name="eye" />
            </button>

            <button
                type="button"
                class="card__tool card__tool--extra"
                style="--i:1"
                data-stock-ask="{{ route('inquiry.stock') }}"
                data-product-id="{{ $product->id }}"
                data-source="listing"
                data-fallback="{{ wa_link('Merhaba, "'.$product->name.'" ürününün stok durumunu öğrenebilir miyim?') }}"
                aria-label="WhatsApp'tan stok sor"
                title="WhatsApp'tan stok sor"
            >
                <x-ay-icon name="whatsapp" :filled="true" />
            </button>
        </div>
    </div>

    {{-- Bilgi --}}
    <div class="card__body">
        @if ($cat)
            <a class="card__cat" href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a>
        @endif

        <h3 class="card__title"><a href="{{ $product->url }}">{{ $product->name }}</a></h3>

        @if ($rating)
            <p class="card__rating">
                <span class="stars-rate" style="--rate:{{ round($rating / 5 * 100) }}%"
                      role="img" aria-label="5 üzerinden {{ number_format($rating, 1, ',', '') }}">
                    <span class="stars-rate__bg" aria-hidden="true">★★★★★</span>
                    <span class="stars-rate__fg" aria-hidden="true">★★★★★</span>
                </span>
                <strong>{{ number_format($rating, 1, ',', '') }}</strong>
                <span class="muted">({{ $product->review_count }})</span>
            </p>
        @endif

        {{-- "Stokta" her kartta yazınca gürültü oluyor ve hiçbir şey söylemiyor.
             Yalnızca bilgi veren durumlar gösterilir: azalan stok, tükenmiş,
             siparişe özel.
             Fiyatın ÜSTÜNDE duruyor — altında olsaydı yalnızca bazı kartlarda
             çıktığı için o kartların düğmeleri komşularından yukarı kayardı. --}}
        @unless ($product->stock_state === 'in_stock')
            <p class="card__stock" data-state="{{ $product->stock_state }}">{{ $product->stock_label }}</p>
        @endunless

        <p class="card__price">
            <strong>{{ money($product->display_price) }}</strong>
            @if ($product->display_compare_at)
                <del>{{ money($product->display_compare_at) }}</del>
            @endif
        </p>

        @if (! $product->is_orderable)
            <a class="btn btn--rect btn--ghost btn--block" href="{{ $product->url }}">Ürünü incele</a>
        @elseif ($product->has_variants)
            <a class="btn btn--rect btn--block" href="{{ $product->url }}">Boy Seçin</a>
        @else
            {{-- Listeden doğrudan sepete atılmaz; müşteri önce ürün sayfasına
                 gider. Hızlı eklemek isteyen kart üzerindeki göz düğmesinden
                 hızlı bakışı açıp oradan ekleyebilir. --}}
            <a class="btn btn--rect btn--block" href="{{ $product->url }}">Ürünü incele</a>
        @endif

        <p class="card__ship" data-ship data-ship-open="{{ $ship['open'] ? '1' : '0' }}">
            <span class="card__ship-open">
                Bugün teslim için <strong data-ship-out>{{ $ship['label'] }}</strong>
            </span>
            <span class="card__ship-closed">Yarın teslim edilir</span>
        </p>
    </div>
</article>
