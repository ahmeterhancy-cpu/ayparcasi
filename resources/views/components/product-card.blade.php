@props(['product', 'reveal' => 'up'])

@php
    $images = $product->images;
    $main = img_url($images[0] ?? null, 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=800&q=70');
    $alt = isset($images[1]) ? img_url($images[1]) : null;
    $cat = $product->relationLoaded('categories') ? $product->categories->first() : null;
    $discount = $product->discount_percent;
@endphp

<article class="card {{ $product->is_orderable ? '' : 'is-out' }}" data-reveal="{{ $reveal }}">
    <a class="card__media" href="{{ $product->url }}" tabindex="-1" aria-hidden="true">
        <img class="card__img" src="{{ $main }}" alt="" loading="lazy" decoding="async" width="640" height="800">
        @if ($alt)
            <img class="card__img card__img--alt" src="{{ $alt }}" alt="" loading="lazy" decoding="async" width="640" height="800">
        @endif

        @unless ($product->is_orderable)
            <span class="card__sold"><span class="badge">Tükendi</span></span>
        @endunless
    </a>

    <div class="card__actions">
        @if ($product->is_orderable && ! $product->has_variants)
            <form method="POST" action="{{ route('cart.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn--light btn--sm btn--block">
                    <x-ay-icon name="cart" /> Sepete ekle
                </button>
            </form>
        @else
            <a href="{{ $product->url }}" class="btn btn--light btn--sm btn--block">
                {{ $product->is_orderable ? 'Boy seç' : 'Ürünü incele' }}
            </a>
        @endif

        <button
            type="button"
            class="btn btn--wa btn--sm btn--block"
            data-stock-ask="{{ route('inquiry.stock') }}"
            data-product-id="{{ $product->id }}"
            data-source="listing"
            data-fallback="{{ wa_link('Merhaba, "'.$product->name.'" ürününün stok durumunu öğrenebilir miyim?') }}"
        >
            <x-ay-icon name="whatsapp" :filled="true" /> Stok bilgisi al
        </button>
    </div>

    <div class="card__body">
        @if ($discount || $product->badge)
            <div class="card__flags">
                @if ($discount)
                    <span class="badge badge--coral">%{{ $discount }} indirim</span>
                @endif
                @if ($product->badge)
                    <span class="badge badge--sun">{{ $product->badge }}</span>
                @endif
            </div>
        @endif

        @if ($cat)
            <span class="card__cat">{{ $cat->name }}</span>
        @endif

        <h3 class="card__title"><a href="{{ $product->url }}">{{ $product->name }}</a></h3>

        <p class="card__price">
            @if ($product->has_variants)
                <span class="card__from">Başlangıç</span>
            @endif
            <span>{{ money($product->display_price) }}</span>
            @if ($product->display_compare_at)
                <del>{{ money($product->display_compare_at) }}</del>
            @endif
        </p>
    </div>
</article>
