@php
    $images = $product->images;
    if (empty($images)) {
        $images = ['https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=1200&q=72'];
    }
    $variants = $product->variants->where('is_active', true)->values();
    $default = $variants->firstWhere('is_default', true) ?? $variants->sortBy('price')->first();
    $cutoffHour = (int) setting('same_day_cutoff_hour', 15);
@endphp

<x-layouts.app :title="$product->name" :description="$product->short_description">

    @push('head')
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->short_description,
            'image' => array_map(fn ($i) => img_url($i), array_slice($images, 0, 4)),
            'offers' => [
                '@type' => 'Offer',
                'price' => (float) $product->display_price,
                'priceCurrency' => 'TRY',
                'availability' => $product->is_orderable
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => $product->url,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endpush

    <div class="wrap">
        <ol class="crumbs">
            <li><a href="{{ route('home') }}">Ana sayfa</a></li>
            <li><a href="{{ route('shop.index') }}">Mağaza</a></li>
            @if ($cat = $product->categories->first())
                <li><a href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a></li>
            @endif
            <li aria-current="page">{{ $product->name }}</li>
        </ol>
    </div>

    <div class="wrap product">
        {{-- Galeri --}}
        <div class="gallery" data-gallery>
            <div class="gallery__main">
                <img data-gallery-main src="{{ img_url($images[0]) }}" alt="{{ $product->name }}"
                     fetchpriority="high" decoding="async" width="1000" height="1250">
            </div>

            @if (count($images) > 1)
                <div class="gallery__thumbs">
                    @foreach ($images as $i => $src)
                        <button type="button"
                                class="gallery__thumb {{ $i === 0 ? 'is-active' : '' }}"
                                data-gallery-thumb="{{ img_url($src) }}"
                                aria-label="{{ $i + 1 }}. görsel">
                            <img src="{{ img_url($src) }}" alt="" loading="lazy" decoding="async">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Bilgi + satın alma --}}
        <div class="product__info">
            @if ($product->categories->isNotEmpty())
                <div class="product__cats">
                    @foreach ($product->categories as $cat)
                        <a class="badge badge--outline" href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endif

            <h1 data-reveal="up" data-split="words">{{ $product->name }}</h1>

            @if ($product->short_description)
                <p class="lead">{{ $product->short_description }}</p>
            @endif

            <div class="product__price">
                <strong data-price-out>{{ money($product->display_price) }}</strong>
                <del data-compare-out @if (! $product->display_compare_at) hidden @endif>
                    {{ $product->display_compare_at ? money($product->display_compare_at) : '' }}
                </del>
                @if ($product->discount_percent)
                    <span class="badge badge--coral">%{{ $product->discount_percent }} indirim</span>
                @endif
            </div>

            <span class="stock" data-stock-out data-state="{{ $product->stock_state }}">
                {{ $product->stock_label }}
            </span>

            {{-- Satın alma kutusu --}}
            <div class="product__buy">
                @if ($product->is_orderable)
                    <form method="POST" action="{{ route('cart.store') }}"
                          data-product-form
                          data-base-price="{{ (float) $product->price }}"
                          data-base-compare="{{ (float) ($product->compare_at_price ?? 0) }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        @if ($variants->isNotEmpty())
                            <div class="sizes" style="margin-bottom:1.1rem">
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

                        @if ($addons->isNotEmpty())
                            <div class="addons" style="margin-bottom:1.1rem">
                                <span class="label">Yanına ekleyin</span>
                                @foreach ($addons as $addon)
                                    <label class="choice choice--check">
                                        <input type="checkbox" name="addons[]" value="{{ $addon->id }}"
                                               data-price="{{ (float) $addon->price }}">
                                        <span class="choice__dot" aria-hidden="true"></span>
                                        <span class="choice__text">
                                            <span class="choice__title">{{ $addon->name }}</span>
                                            @if ($addon->description)
                                                <span class="choice__meta">{{ $addon->description }}</span>
                                            @endif
                                        </span>
                                        <span class="choice__price">+{{ money($addon->price) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="product__buy-row">
                            <div class="qty">
                                <button type="button" data-qty-step="-1" aria-label="Azalt"><x-ay-icon name="minus" /></button>
                                <label class="sr-only" for="adet">Adet</label>
                                <input type="number" name="quantity" id="adet" value="1" min="1" max="99" inputmode="numeric">
                                <button type="button" data-qty-step="1" aria-label="Artır"><x-ay-icon name="plus" /></button>
                            </div>

                            <button class="btn" type="submit" data-magnetic="0.18">
                                <x-ay-icon name="cart" /> Sepete ekle
                            </button>
                        </div>
                    </form>
                @else
                    <p style="font-weight:600">Bu tasarım şu an tükendi.</p>
                    <p class="muted" style="font-size:.9rem">
                        Benzerini hazırlayabiliriz ya da ne zaman geleceğini söyleyebiliriz — WhatsApp'tan sorun.
                    </p>
                @endif

                {{-- WhatsApp'tan stok bilgisi al --}}
                <button type="button" class="btn btn--wa btn--block"
                        data-stock-ask="{{ route('inquiry.stock') }}"
                        data-product-id="{{ $product->id }}"
                        data-source="product"
                        data-fallback="{{ wa_link('Merhaba, "'.$product->name.'" ürününün stok durumunu öğrenebilir miyim? '.$product->url) }}">
                    <x-ay-icon name="whatsapp" :filled="true" /> WhatsApp'tan stok bilgisi al
                </button>

                <p class="muted" style="font-size:.82rem;text-align:center">
                    Ödemeyi kartla yapabilir, dilerseniz siparişi WhatsApp üzerinden de tamamlayabilirsiniz.
                </p>
            </div>

            <ul class="product__meta">
                <li>
                    <x-ay-icon name="clock" />
                    <span>
                        Bugün saat {{ sprintf('%02d:00', $cutoffHour) }}'e kadar verilen siparişler
                        {{ $product->same_day ? 'aynı gün teslim edilir.' : 'ertesi gün teslim edilir.' }}
                    </span>
                </li>
                <li>
                    <x-ay-icon name="leaf" />
                    <span>Siparişten sonra hazırlanır; hazır buket tutmuyoruz.</span>
                </li>
                <li>
                    <x-ay-icon name="gift" />
                    <span>Elle yazılmış kart notu ücretsiz eklenir.</span>
                </li>
                <li>
                    <x-ay-icon name="shield" />
                    <span>Tazelik garantisi — sorun olursa 24 saat içinde yenisini göndeririz.</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- Açıklama / içerik / bakım --}}
    @if ($product->description || $product->contents || $product->care_notes)
        <section class="section section--sand section--tight">
            <div class="wrap" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.3fr);gap:clamp(2rem,5vw,4rem);align-items:start">
                <div>
                    <span class="eyebrow">Detaylar</span>
                    <h2 style="margin-top:.7rem" data-reveal="up">Bu tasarım hakkında</h2>
                </div>

                <div class="acc" data-accordion>
                    @if ($product->description)
                        <div class="acc__item is-open" data-accordion-item>
                            <button class="acc__trigger" type="button" data-accordion-trigger aria-expanded="true">
                                Açıklama <span class="acc__sign" aria-hidden="true"></span>
                            </button>
                            <div class="acc__panel" data-accordion-panel>
                                <div class="prose">{!! nl2br(e($product->description)) !!}</div>
                            </div>
                        </div>
                    @endif

                    @if ($product->contents)
                        <div class="acc__item" data-accordion-item>
                            <button class="acc__trigger" type="button" data-accordion-trigger aria-expanded="false">
                                İçindekiler <span class="acc__sign" aria-hidden="true"></span>
                            </button>
                            <div class="acc__panel" data-accordion-panel>
                                <div class="prose">{!! nl2br(e($product->contents)) !!}</div>
                            </div>
                        </div>
                    @endif

                    @if ($product->care_notes)
                        <div class="acc__item" data-accordion-item>
                            <button class="acc__trigger" type="button" data-accordion-trigger aria-expanded="false">
                                Bakım önerisi <span class="acc__sign" aria-hidden="true"></span>
                            </button>
                            <div class="acc__panel" data-accordion-panel>
                                <div class="prose">{!! nl2br(e($product->care_notes)) !!}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Benzerleri --}}
    @if ($related->isNotEmpty())
        <section class="section section--tight">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Bunlar da yakışır</span>
                        <h2 data-reveal="up">Benzer tasarımlar</h2>
                    </div>
                </div>

                <div class="grid-products">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.app>
