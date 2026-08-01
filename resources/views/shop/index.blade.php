@php
    $term = $term ?? null;
    $heading = $category?->name ?? ($term !== null ? 'Arama sonuçları' : 'Tüm Ürünler');
    $activeSlug = $category?->slug;
@endphp

<x-layouts.app :title="$heading" :description="$category?->meta_description">

    <div class="wrap">
        <ol class="crumbs">
            <li><a href="{{ route('home') }}">Ana sayfa</a></li>
            <li><a href="{{ route('shop.index') }}">Mağaza</a></li>
            @if ($category?->parent)
                <li><a href="{{ route('shop.category', $category->parent->slug) }}">{{ $category->parent->name }}</a></li>
            @endif
            @if ($category)
                <li aria-current="page">{{ $category->name }}</li>
            @endif
        </ol>
    </div>

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">{{ $term !== null ? 'Arama' : 'Koleksiyon' }}</span>
            <h1 data-reveal="up" data-split="words">{{ $term ? '“'.$term.'”' : $heading }}</h1>
            {{-- Kategori kendi açıklamasını gösterir; "Tüm Ürünler" sayfasında
                 genel bir tanıtım metni yok, ürünler doğrudan başlasın. --}}
            @if ($category?->description)
                <p class="lead" data-reveal="up">{{ $category->description }}</p>
            @endif
        </div>
    </header>

    <div class="wrap shop">
        {{-- Mobilde bu panel sağdan açılan bir çekmeceye dönüşür; masaüstünde
             normal yan sütundur. Aynı işaretleme iki yerde tekrarlanmaz ki
             form alanlarının id'leri çakışmasın. --}}
        <aside class="shop__side" id="shop-filtreler" data-escapable
               aria-label="Kategoriler ve filtreler">
            <div class="shop__side-head">
                <h2>Kategoriler ve filtreler</h2>
                <button type="button" class="shop__side-close"
                        data-toggle="#shop-filtreler" aria-label="Kapat">
                    <x-ay-icon name="close" />
                </button>
            </div>

            <div>
                <h2 class="filter__title">Kategoriler</h2>
                <div class="filter__list">
                    <a href="{{ route('shop.index') }}" class="{{ ! $category && $term === null ? 'is-on' : '' }}">
                        Tüm Ürünler
                    </a>

                    @foreach ($categories as $cat)
                        <a href="{{ route('shop.category', $cat->slug) }}"
                           class="{{ $activeSlug === $cat->slug ? 'is-on' : '' }}">
                            {{ $cat->name }}
                        </a>

                        @if ($cat->children->isNotEmpty())
                            <div class="filter__sub">
                                @foreach ($cat->children as $child)
                                    <a href="{{ route('shop.category', $child->slug) }}"
                                       class="{{ $activeSlug === $child->slug ? 'is-on' : '' }}">
                                        {{ $child->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Filtreler --}}
            <form method="GET" class="filters">
                @if ($sort)
                    <input type="hidden" name="sirala" value="{{ $sort }}">
                @endif
                @if ($term)
                    <input type="hidden" name="q" value="{{ $term }}">
                @endif

                <h2 class="filter__title">Fiyat aralığı</h2>
                <div class="filters__price">
                    <label class="sr-only" for="min">En az</label>
                    <input class="input" type="number" name="min" id="min" inputmode="numeric"
                           min="{{ $priceBounds['lo'] }}" max="{{ $priceBounds['hi'] }}"
                           placeholder="{{ $priceBounds['lo'] }}" value="{{ request('min') }}">
                    <span aria-hidden="true">–</span>
                    <label class="sr-only" for="max">En çok</label>
                    <input class="input" type="number" name="max" id="max" inputmode="numeric"
                           min="{{ $priceBounds['lo'] }}" max="{{ $priceBounds['hi'] }}"
                           placeholder="{{ $priceBounds['hi'] }}" value="{{ request('max') }}">
                </div>

                <h2 class="filter__title" style="margin-top:1.25rem">Filtrele</h2>
                <div class="filters__checks">
                    @foreach ([
                        'indirimli' => 'İndirimdekiler',
                        'ayni_gun' => 'Aynı gün teslim',
                        'stokta' => 'Stokta olanlar',
                    ] as $key => $label)
                        <label class="filters__check">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(request()->boolean($key))>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <button class="btn btn--rect btn--sm btn--block" type="submit" style="margin-top:.9rem">
                    Filtreleri uygula
                </button>

                @if ($filters)
                    <a class="link-u" style="display:inline-flex;margin-top:.75rem"
                       href="{{ $category ? route('shop.category', $category->slug) : route('shop.index') }}">
                        Filtreleri temizle
                    </a>
                @endif
            </form>

            <div style="padding:1.2rem;border-radius:var(--radius-lg);background:var(--turq-3)">
                <h2 class="filter__title" style="color:var(--sea)">Ne alacağınıza karar veremediniz mi?</h2>
                <p style="font-size:.88rem;color:var(--ink-2);margin-bottom:1rem">
                    Kime, hangi vesileyle göndereceğinizi yazın; birkaç öneri hazırlayalım.
                </p>
                <a class="btn btn--wa btn--sm btn--block"
                   href="{{ wa_link('Merhaba, bir hediye seçmekte yardım eder misiniz?') }}"
                   target="_blank" rel="noopener">
                    <x-ay-icon name="whatsapp" :filled="true" /> Öneri iste
                </a>
            </div>
        </aside>

        {{-- Çekmece perdesi — yalnız mobilde ve panel açıkken görünür.
             Kardeş seçiciyle bağlı olduğu için panelin hemen ardında durmalı. --}}
        <div class="shop__scrim" data-toggle="#shop-filtreler" aria-hidden="true"></div>

        <div>
            <button type="button" class="btn btn--rect btn--block shop__filter-btn"
                    data-toggle="#shop-filtreler"
                    aria-expanded="false" aria-controls="shop-filtreler">
                <x-ay-icon name="menu" /> Kategoriler ve filtreler
                @if ($filters)
                    <span class="shop__filter-count">{{ count($filters) }}</span>
                @endif
            </button>

            @if ($filters)
                <div class="filter-chips">
                    @foreach ($filters as $filter)
                        <a class="filter-chip" href="{{ request()->fullUrlWithQuery([$filter['key'] => null]) }}">
                            {{ $filter['label'] }}
                            <span aria-hidden="true">×</span>
                            <span class="sr-only">filtresini kaldır</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="shop__bar">
                <p class="shop__count">
                    <strong>{{ $products->total() }}</strong> ürün
                    @if ($products->hasPages())
                        · {{ $products->currentPage() }} / {{ $products->lastPage() }}. sayfa
                    @endif
                </p>

                <form class="sort" method="GET">
                    @foreach (request()->except(['sirala', 'page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <label for="sirala">Sırala</label>
                    <select class="select" name="sirala" id="sirala" onchange="this.form.submit()">
                        <option value="">Önerilen</option>
                        <option value="yeni" @selected($sort === 'yeni')>En yeni</option>
                        <option value="ucuz" @selected($sort === 'ucuz')>Artan fiyat</option>
                        <option value="pahali" @selected($sort === 'pahali')>Azalan fiyat</option>
                    </select>
                    <noscript><button class="btn btn--sm" type="submit">Uygula</button></noscript>
                </form>
            </div>

            @if ($products->isEmpty())
                <div class="empty">
                    <x-ay-icon name="flower" style="width:44px;height:44px;color:var(--turq)" />
                    <h2>Burada henüz bir şey yok</h2>
                    <p class="lead">
                        Aradığınız ürünü bulamadıysanız WhatsApp'tan yazın — elimizde olmayanı da temin edebiliyoruz.
                    </p>
                    <div style="display:flex;gap:.6rem;flex-wrap:wrap;justify-content:center">
                        <a class="btn" href="{{ route('shop.index') }}">Tüm ürünlere dön</a>
                        <a class="btn btn--wa" href="{{ wa_link('Merhaba, aradığım bir ürün var.') }}" target="_blank" rel="noopener">
                            <x-ay-icon name="whatsapp" :filled="true" /> Bize sorun
                        </a>
                    </div>
                </div>
            @else
                <div class="grid-products">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                {{ $products->links() }}
            @endif
        </div>
    </div>

</x-layouts.app>
