<x-layouts.app title="Sepetiniz">

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Sepet</span>
            <h1 data-reveal="up">Sepetiniz</h1>
        </div>
    </header>

    @if ($lines->isEmpty())
        <div class="wrap">
            <div class="empty">
                <x-ay-icon name="cart" style="width:42px;height:42px;color:var(--turq)" />
                <h2>Sepetiniz henüz boş</h2>
                <p class="lead">Bir buket seçin ya da ne aradığınızı bize söyleyin; birlikte bakalım.</p>
                <div style="display:flex;gap:.6rem;flex-wrap:wrap;justify-content:center">
                    <a class="btn" href="{{ route('shop.index') }}">Ürünlere göz at</a>
                    <a class="btn btn--wa" href="{{ wa_link('Merhaba, bir hediye arıyorum.') }}" target="_blank" rel="noopener">
                        <x-ay-icon name="whatsapp" :filled="true" /> WhatsApp'tan sor
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="wrap cart">
            <div>
                @foreach ($lines as $line)
                    <div class="cart__line">
                        <a class="cart__thumb" href="{{ $line['product']->url }}">
                            <img src="{{ img_url($line['product']->hero_image, 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=300&q=70') }}"
                                 alt="" loading="lazy" decoding="async">
                        </a>

                        <div class="cart__line-body">
                            <h2 class="cart__line-title">
                                <a href="{{ $line['product']->url }}">{{ $line['product']->name }}</a>
                            </h2>

                            @if ($line['variant'])
                                <p class="cart__line-meta">Boy: {{ $line['variant']->name }}</p>
                            @endif

                            @if ($line['addons']->isNotEmpty())
                                <p class="cart__line-meta">
                                    Ekstra: {{ $line['addons']->pluck('name')->implode(', ') }}
                                </p>
                            @endif

                            <p class="cart__line-meta">Birim: {{ money($line['unit_price']) }}</p>

                            <div class="cart__line-tools">
                                <form method="POST" action="{{ route('cart.update', $line['key']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="qty">
                                        <label class="sr-only" for="q-{{ $line['key'] }}">Adet</label>
                                        <input type="number" name="quantity" id="q-{{ $line['key'] }}"
                                               value="{{ $line['quantity'] }}" min="0" max="99"
                                               inputmode="numeric" data-auto-submit>
                                    </div>
                                    <noscript><button class="btn btn--sm" type="submit">Güncelle</button></noscript>
                                </form>

                                <form method="POST" action="{{ route('cart.destroy', $line['key']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="cart__remove" type="submit">Kaldır</button>
                                </form>
                            </div>
                        </div>

                        <span class="cart__line-total">{{ money($line['line_total']) }}</span>
                    </div>
                @endforeach

                <a class="link-u" href="{{ route('shop.index') }}" style="display:inline-flex;margin-top:1.75rem">
                    Alışverişe devam et
                </a>
            </div>

            {{-- Özet --}}
            <aside class="summary">
                <h2 style="font-size:1.15rem">Özet</h2>

                <div class="summary__row">
                    <span>Ara toplam</span>
                    <span>{{ money($summary['subtotal']) }}</span>
                </div>

                @if ($summary['coupon'])
                    <div class="summary__row summary__row--discount">
                        <span>İndirim ({{ $summary['coupon']->code }})</span>
                        <span>-{{ money($summary['discount']) }}</span>
                    </div>
                @endif

                <div class="summary__row">
                    <span>Teslimat</span>
                    <span class="muted">Kasada hesaplanır</span>
                </div>

                <div class="summary__row summary__row--total">
                    <span>Toplam</span>
                    <span>{{ money(max(0, $summary['subtotal'] - $summary['discount'])) }}</span>
                </div>

                {{-- Kupon --}}
                @if ($summary['coupon'])
                    <div class="coupon-on">
                        <span><x-ay-icon name="check" style="width:15px;height:15px;display:inline" /> {{ $summary['coupon']->code }} uygulandı</span>
                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cart__remove">Kaldır</button>
                        </form>
                    </div>
                @else
                    <form class="coupon" method="POST" action="{{ route('cart.coupon.apply') }}">
                        @csrf
                        <label class="sr-only" for="kupon">Kupon kodu</label>
                        <input class="input" type="text" name="code" id="kupon" placeholder="Kupon kodu" autocomplete="off">
                        <button class="btn btn--ghost btn--sm" type="submit">Uygula</button>
                    </form>
                @endif

                <a class="btn btn--block" href="{{ route('checkout.index') }}" data-magnetic="0.16">
                    Siparişi tamamla <x-ay-icon name="arrow-right" />
                </a>

                <p class="muted" style="font-size:.8rem;text-align:center">
                    Kartla ödeyebilir ya da WhatsApp'tan onaylayabilirsiniz.
                </p>
            </aside>
        </div>

        @if ($suggested->isNotEmpty())
            <section class="section section--tight section--sand">
                <div class="wrap">
                    <div class="section-head">
                        <div class="section-head__text">
                            <span class="eyebrow">Yanına yakışır</span>
                            <h2>Bunlar da ilginizi çekebilir</h2>
                        </div>
                    </div>
                    <div class="grid-products">
                        @foreach ($suggested as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endif

</x-layouts.app>
