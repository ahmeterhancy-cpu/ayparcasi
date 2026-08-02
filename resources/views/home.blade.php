@php
    $heroImage = img_url(setting('hero_image'), 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=2000&q=72');
    $cutoffHour = (int) setting('same_day_cutoff_hour', 15);
    $cutoffMinutes = $cutoffHour * 60;

    /* Teslimat hattı — dükkânın günlük güzergâhı, batıdan doğuya.
       Ada geneli bölgeler bu hattın altındaki kartlarda listeleniyor. */
    $routeStops = ['Alsancak', 'Karaoğlanoğlu', 'Zeytinlik', 'Girne', 'Karakum', 'Ozanköy', 'Çatalköy'];

    /* Duraklar eğrinin ÜSTÜNE otursun diye koordinatlar elle seçilmiyor,
       kübik Bézier formülünden hesaplanıyor. Böylece eğriyi değiştirsek
       bile noktalar hattan kopmuyor. */
    $buildRoute = function (array $names, array $p0, array $p1, array $p2, array $p3) {
        $at = function (float $t) use ($p0, $p1, $p2, $p3): array {
            $u = 1 - $t;

            return [
                round($u ** 3 * $p0[0] + 3 * $u ** 2 * $t * $p1[0] + 3 * $u * $t ** 2 * $p2[0] + $t ** 3 * $p3[0], 1),
                round($u ** 3 * $p0[1] + 3 * $u ** 2 * $t * $p1[1] + 3 * $u * $t ** 2 * $p2[1] + $t ** 3 * $p3[1], 1),
            ];
        };

        $n = count($names);

        return [
            'path' => "M{$p0[0]} {$p0[1]} C {$p1[0]} {$p1[1]}, {$p2[0]} {$p2[1]}, {$p3[0]} {$p3[1]}",
            'stops' => collect($names)->map(function (string $name, int $i) use ($at, $n) {
                // Uçlardan pay bırakıp duraklar eğriye eşit aralıkla dağıtılır
                [$x, $y] = $at(0.06 + $i * (0.88 / max(1, $n - 1)));

                return [
                    'name' => $name,
                    'x' => $x,
                    'y' => $y,
                    // Geniş sürümde etiketler bir üstte bir altta
                    'above' => $i % 2 === 0,
                ];
            }),
        ];
    };

    /* Kontrol noktalarının X'leri aralığın tam 1/3 ve 2/3'üne konuyor.
       Böylece x(t) t'ye göre DOĞRUSAL oluyor ve duraklar yatayda eşit
       aralıklı diziliyor; aksi halde eğrinin uçlarında sıkışıyorlardı
       (Alsancak ile Karaoğlanoğlu üst üste biniyordu). */

    // Geniş ekran: viewBox 820×210 — 58 → 762 arası, 1/3 ve 2/3: 293 ve 527
    $wide = $buildRoute($routeStops, [58, 132], [293, 34], [527, 34], [762, 132]);
    $routePath = $wide['path'];
    $routeStops = $wide['stops'];

    // Dar ekran: viewBox 360×150 — 18 → 342 arası, 1/3 ve 2/3: 126 ve 234
    $narrow = $buildRoute($routeStops->pluck('name')->all(), [18, 62], [126, 20], [234, 20], [342, 62]);
    $routePathNarrow = $narrow['path'];
    $routeStopsNarrow = $narrow['stops'];
@endphp

<x-layouts.app :transparent-header="true">

    {{-- ===================================================================
         1. HERO — mozaik perde açılışı, kaydırdıkça çerçeveye dönüşür
         =================================================================== --}}
    <section class="hero" data-scrub data-scrub-range="cover" aria-label="Ay Parçası">
        <div class="hero__sticky">
            <div class="hero__frame">
                <img class="hero__img" src="{{ $heroImage }}" alt="" fetchpriority="high" decoding="async">
                <div class="hero__veil"></div>
            </div>

            {{-- Açılışta karo karo kalkan perde --}}
            <div class="hero__curtain" aria-hidden="true">
                @for ($row = 0; $row < 3; $row++)
                    @for ($col = 0; $col < 6; $col++)
                        <i style="--i:{{ $row + $col }}"></i>
                    @endfor
                @endfor
            </div>

            <div class="hero__content">
                <p class="hero__eyebrow">{{ setting('hero_eyebrow', 'Kıbrıs · Aynı gün teslimat') }}</p>

                {{-- --split-delay: perde kalkmadan yazı çıkmasın --}}
                <h1 class="hero-type hero__title" data-reveal="fade" data-split="words" style="--split-delay:620ms">
                    {{ setting('hero_title', 'Bir çiçek, bir cümleden fazlasını söyler') }}
                </h1>

                <p class="hero__sub">
                    {{ setting('hero_subtitle', 'Her buket dükkânımızda, siparişiniz geldikten sonra elde hazırlanır. Bugün sipariş verin, bugün kapısında olsun.') }}
                </p>

                <div class="hero__cta">
                    <a class="btn btn--sun btn--lg" href="{{ route('shop.index') }}" data-magnetic="0.22">
                        Buketleri gör <x-ay-icon name="arrow-right" />
                    </a>
                    <a class="btn btn--light btn--lg" href="{{ wa_link('Merhaba, bir buket için öneri alabilir miyim?') }}"
                       target="_blank" rel="noopener">
                        <x-ay-icon name="whatsapp" :filled="true" /> WhatsApp'tan sor
                    </a>
                </div>
            </div>

            <div class="hero__scroll" aria-hidden="true">
                <span>Kaydır</span>
                <i></i>
            </div>
        </div>
    </section>

    {{-- ===================================================================
         2. AYNI GÜN SAYACI — canlı, gerçek bilgi
         =================================================================== --}}
    <section class="cutoff" data-cutoff="{{ $cutoffMinutes }}" data-state="open">
        <div class="wrap cutoff__inner">
            <p class="cutoff__msg">
                <span class="cutoff__dot"></span>
                <span class="cutoff__open">
                    Bugün teslim için <strong data-cutoff-out>—</strong> kaldı.
                </span>
                <span class="cutoff__closed">
                    Bugünün siparişleri kapandı — yarın ilk teslimat sizin olsun.
                </span>
            </p>

            <div class="cutoff__meta">
                <span><x-ay-icon name="clock" /> Sipariş kapanışı {{ sprintf('%02d:00', $cutoffHour) }}</span>
                <span><x-ay-icon name="truck" /> {{ $zones->where('same_day', true)->pluck('name')->take(4)->implode(', ') ?: 'Tüm bölgeler' }}</span>
                <span><x-ay-icon name="leaf" /> Siparişten sonra hazırlanır</span>
            </div>
        </div>
    </section>

    {{-- ===================================================================
         3. ÖZEL GÜNLER — genişleyen paneller
         =================================================================== --}}
    @if ($occasions->isNotEmpty())
        <section class="section" data-scrub data-scrub-range="enter">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Koleksiyonlar</span>
                        <h2 data-reveal="up">Özel günler ve favoriler</h2>
                    </div>
                    <p class="lead" style="max-width:34ch" data-reveal="up">
                        En çok gönderilen kategoriler. Birinden başlayın, gerisi kolay.
                    </p>
                </div>
            </div>

            <div class="wrap wrap--wide">
                <div class="panels" data-panels data-swap data-reveal="fade">
                    {{-- Akordeon 8'e kadar rahat taşır; fazlasında kapalı
                         şeritler daralıp dikey başlıklar sıkışır. --}}
                    @foreach ($occasions->take(8) as $i => $cat)
                        @php
                            $count = $cat->allProducts()->count();
                            $img = img_url($cat->image, 'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=1000&q=70');
                        @endphp
                        <a class="panel" href="{{ route('shop.category', $cat->slug) }}" data-panel data-swap-item>
                            <img class="panel__img" src="{{ $img }}" alt="" loading="lazy" decoding="async">
                            <span class="panel__shade"></span>
                            <span class="panel__count">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>

                            <div class="panel__body">
                                <h3 class="panel__title">{{ $cat->name }}</h3>
                                <p class="panel__desc">{{ $cat->description ?: $count.' tasarım hazır' }}</p>
                                <span class="panel__link link-u">Koleksiyonu aç <x-ay-icon name="arrow-right" /></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         4. ÇOK SATANLAR
         =================================================================== --}}
    @if ($featured->isNotEmpty())
        <section class="section section--tight">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">En çok gönderilenler</span>
                        <h2 data-reveal="up">Gözden kaçmayanlar</h2>
                    </div>
                    <a class="link-u" href="{{ route('shop.index') }}">Tümünü gör <x-ay-icon name="arrow-right" /></a>
                </div>

                <div class="grid-products">
                    @foreach ($featured as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         5. YENİ ÇİÇEKLER VİTRİNİ — solda liste, ortada kart, sağda ürünler
         =================================================================== --}}
    @if ($newest->count() >= 4)
        @php
            // Sağ sütun iki kart alıyor; üçüncüsü bu genişlikte alt satıra
            // sarkıp dengesiz duruyordu.
            $showcaseCards = $newest->take(2);
            $showcaseList = $newest->slice(2, 5);
        @endphp

        <section class="section section--tight">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Tezgâhtan yeni çıktı</span>
                        <h2 data-reveal="up">Yeni Çiçekler</h2>
                    </div>
                    <a class="link-u" href="{{ route('shop.index', ['sirala' => 'yeni']) }}">
                        Tümünü gör <x-ay-icon name="arrow-right" />
                    </a>
                </div>

                <div class="showcase">
                    {{-- Sol: küçük liste --}}
                    @if ($showcaseList->isNotEmpty())
                        <ul class="mini-list" data-stagger="60">
                            @foreach ($showcaseList as $item)
                                <li>
                                    <a class="mini" href="{{ $item->url }}">
                                        <img class="mini__img" src="{{ img_url($item->images[0] ?? null) }}"
                                             alt="" loading="lazy" decoding="async" width="120" height="120">
                                        <span class="mini__body">
                                            <span class="mini__name">{{ $item->name }}</span>
                                            <span class="mini__price">
                                                <strong>{{ money($item->display_price) }}</strong>
                                                @if ($item->display_compare_at)
                                                    <del>{{ money($item->display_compare_at) }}</del>
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Orta: panelden yönetilen dikey tanıtım kartı --}}
                    @if ($showcase)
                        @php $url = $showcase->link ?: route('shop.index', ['indirimli' => 1]); @endphp
                        <a class="promo promo--tall" href="{{ $url }}" data-reveal="up">
                            @if ($showcase->image)
                                <img class="promo__img" src="{{ img_url($showcase->image) }}" alt=""
                                     loading="lazy" decoding="async">
                            @endif
                            <span class="promo__shade promo__shade--top"></span>

                            <span class="promo__body">
                                @if ($showcase->eyebrow)
                                    <span class="promo__eyebrow">{{ $showcase->eyebrow }}</span>
                                @endif
                                @if ($showcase->title)
                                    <span class="promo__title">{{ $showcase->title }}</span>
                                @endif
                                @if ($showcase->subtitle)
                                    <span class="promo__sub">{{ $showcase->subtitle }}</span>
                                @endif
                                <span class="promo__cta">
                                    {{ $showcase->cta_label ?: 'İncele' }}
                                    <x-ay-icon name="arrow-right" />
                                </span>
                            </span>
                        </a>
                    @endif

                    {{-- Sağ: ürün kartları --}}
                    <div class="showcase__cards">
                        @foreach ($showcaseCards as $item)
                            <x-product-card :product="$item" />
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         6. KAMPANYA ŞERİTLERİ — panelden yönetilir (Duyurular › yerleşim: promo)
         =================================================================== --}}
    @if ($promos->isNotEmpty())
        <section class="section section--tight">
            <div class="wrap promos">
                @foreach ($promos as $promo)
                    @php $url = $promo->link ?: route('shop.index'); @endphp
                    <a class="promo" href="{{ $url }}" data-reveal="up">
                        @if ($promo->image)
                            <img class="promo__img" src="{{ img_url($promo->image) }}" alt=""
                                 loading="lazy" decoding="async">
                        @endif
                        <span class="promo__shade"></span>

                        <span class="promo__body">
                            @if ($promo->eyebrow)
                                <span class="promo__eyebrow">{{ $promo->eyebrow }}</span>
                            @endif
                            @if ($promo->title)
                                <span class="promo__title">{{ $promo->title }}</span>
                            @endif
                            @if ($promo->subtitle)
                                <span class="promo__sub">{{ $promo->subtitle }}</span>
                            @endif
                            <span class="promo__cta">
                                {{ $promo->cta_label ?: 'Koleksiyonu gör' }}
                                <x-ay-icon name="arrow-right" />
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===================================================================
         7. TESLİMAT ROTASI — SVG kaydırdıkça çizilir
         =================================================================== --}}
    @if ($zones->isNotEmpty())
        <section class="section route" data-scrub data-scrub-range="enter">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Nereye götürelim</span>
                        {{-- Teslimat yalnız güzergâhta; "adanın her yeri"
                             artık doğru değildi. --}}
                        <h2 data-reveal="up">Alsancak'tan Çatalköy'e gidiyoruz</h2>
                    </div>
                    <p class="lead" style="max-width:32ch" data-reveal="up">
                        Bölgenizi seçtiğinizde teslimat ücreti ve en erken teslim günü anında görünür.
                    </p>
                </div>

                <div class="route__art">
                    <svg class="route__svg" viewBox="0 0 820 210" aria-hidden="true">
                        {{-- Hat baştan sona kesiksiz çizili; kaydırdıkça
                             çizilme efekti ve arkasındaki kesikli iz kaldırıldı. --}}
                        <path class="route__line" d="{{ $routePath }}" />

                        @foreach ($routeStops as $stop)
                            <g>
                                <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" r="12"
                                        fill="var(--sun)" opacity=".22" />
                                <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" r="6.5" fill="var(--sun)" />
                                <text x="{{ $stop['x'] }}" y="{{ $stop['y'] + ($stop['above'] ? -19 : 27) }}"
                                      text-anchor="middle" font-size="13" font-weight="700"
                                      fill="var(--ink)" font-family="Manrope, sans-serif">{{ $stop['name'] }}</text>
                            </g>
                        @endforeach
                    </svg>

                    {{-- Mobil sürüm: aynı hat, dar viewBox'la kurulmuş.
                         Geniş sürüm 375px'e sığdırılınca ölçek 0.46'ya
                         düşüyor ve durak adları okunmaz oluyordu; burada
                         ölçek ~1 olduğu için yazı boyu gerçek boyunda kalıyor.
                         Yedi ad yan yana sığmadığından etiketler eğik. --}}
                    <svg class="route__svg route__svg--narrow" viewBox="0 0 360 150" aria-hidden="true">
                        <path class="route__line" d="{{ $routePathNarrow }}" />

                        @foreach ($routeStopsNarrow as $stop)
                            <g>
                                <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" r="9"
                                        fill="var(--sun)" opacity=".22" />
                                <circle cx="{{ $stop['x'] }}" cy="{{ $stop['y'] }}" r="5" fill="var(--sun)" />
                                {{-- rotate NEGATİF: pozitif açı saat yönünde
                                     döndürüp etiketi hattın ÜSTÜNE atıyor. --}}
                                <text x="{{ $stop['x'] }}" y="{{ $stop['y'] + 15 }}" text-anchor="end"
                                      transform="rotate(-55 {{ $stop['x'] }} {{ $stop['y'] + 15 }})"
                                      font-size="12" font-weight="700"
                                      fill="var(--ink)" font-family="Manrope, sans-serif">{{ $stop['name'] }}</text>
                            </g>
                        @endforeach
                    </svg>

                    {{-- SVG'ler aria-hidden; ekran okuyucu için metin karşılık --}}
                    <p class="sr-only">
                        Teslimat hattımız: {{ $routeStops->pluck('name')->implode(', ') }}.
                    </p>
                </div>

                <div class="route__zones" data-stagger="70">
                    {{-- Sıra hattakiyle aynı: önce güzergâh durakları, sonra
                         ada geneli. "Ana bölge" rozeti kaldırıldı — ana bölge
                         tek bir köy değil, güzergâhın tamamı; rozet ilk kartı
                         (Alsancak) yanlış biçimde öne çıkarıyordu. --}}
                    @foreach ($zones as $zone)
                        <div class="zone-card">
                            <span class="zone-card__name">{{ $zone->name }}</span>
                            <span class="zone-card__fee">
                                {{ (float) $zone->fee > 0 ? money($zone->fee) : 'Ücretsiz' }}
                            </span>
                            <span class="zone-card__note">
                                {{ $zone->same_day ? 'Aynı gün teslim' : 'Ertesi gün teslim' }}
                                @if ($zone->free_over)
                                    · {{ money($zone->free_over) }} üzeri ücretsiz
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         8. YORUMLAR — tek büyük alıntı, kaydırdıkça değişir
         =================================================================== --}}
    @if ($testimonials->isNotEmpty())
        <section class="section section--sand quotes" data-scrub data-scrub-range="cover" data-swap
                 style="min-height:150vh">
            <div class="wrap" style="position:sticky;top:calc(var(--header-h) + 4vh);padding-block:6vh">
                <span class="eyebrow" style="justify-content:center">Müşterilerimiz</span>

                <div class="quotes__stage">
                    @foreach ($testimonials as $i => $t)
                        <figure class="quote {{ $i === 0 ? 'is-active' : '' }}" data-swap-item>
                            <span class="stars" aria-label="{{ $t->rating }} yıldız">
                                @for ($s = 0; $s < $t->rating; $s++)
                                    <x-ay-icon name="star" :filled="true" />
                                @endfor
                            </span>
                            <p>“{{ $t->body }}”</p>
                            <cite>{{ $t->name }}@if ($t->city) · {{ $t->city }}@endif</cite>
                        </figure>
                    @endforeach
                </div>

                <div class="quotes__dots" aria-hidden="true">
                    @foreach ($testimonials as $i => $t)
                        <i class="{{ $i === 0 ? 'is-on' : '' }}"></i>
                    @endforeach
                </div>
            </div>
        </section>

        @push('scripts')
        <script>
            // Aktif alıntının noktası da yansın
            document.querySelectorAll('.quotes[data-swap]').forEach((s) => {
                const dots = [...s.querySelectorAll('.quotes__dots i')];
                s.addEventListener('swap', (e) => {
                    dots.forEach((d, k) => d.classList.toggle('is-on', k === e.detail.index));
                });
            });
        </script>
        @endpush
    @endif

    {{-- ===================================================================
         9. SSS
         =================================================================== --}}
    @if ($faqs->isNotEmpty())
        <section class="section">
            <div class="wrap" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:clamp(2rem,5vw,5rem);align-items:start">
                <div>
                    <span class="eyebrow">Merak edilenler</span>
                    <h2 style="margin-block:.8rem 1.1rem" data-reveal="up">Aklınızda soru kalmasın</h2>
                    <p class="lead">Aradığınızı bulamadıysanız WhatsApp'tan yazın; genelde birkaç dakika içinde dönüyoruz.</p>
                    <a class="btn btn--wa" style="margin-top:1.5rem"
                       href="{{ wa_link('Merhaba, bir sorum var.') }}" target="_blank" rel="noopener">
                        <x-ay-icon name="whatsapp" :filled="true" /> Soru sor
                    </a>
                </div>

                <div class="acc" data-accordion>
                    @foreach ($faqs as $i => $faq)
                        <div class="acc__item {{ $i === 0 ? 'is-open' : '' }}" data-accordion-item>
                            <button class="acc__trigger" type="button" data-accordion-trigger
                                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                                {{ $faq->question }}
                                <span class="acc__sign" aria-hidden="true"></span>
                            </button>
                            <div class="acc__panel" data-accordion-panel>
                                <div>{!! nl2br(e($faq->answer)) !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         10. GÜNLÜK
         =================================================================== --}}
    @if ($posts->isNotEmpty())
        <section class="section section--tight">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Günlük</span>
                        <h2 data-reveal="up">Çiçekle yaşamak üzerine</h2>
                    </div>
                    <a class="link-u" href="{{ route('page.blog') }}">Tüm yazılar <x-ay-icon name="arrow-right" /></a>
                </div>

                <div class="journal" data-stagger="90">
                    @foreach ($posts as $post)
                        <article class="journal__item">
                            <a href="{{ route('page.post', $post->slug) }}">
                                <figure>
                                    <img src="{{ img_url($post->cover, 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=900&q=70') }}"
                                         alt="" loading="lazy" decoding="async">
                                </figure>
                                <span class="journal__meta">{{ $post->published_at?->translatedFormat('d F Y') }}</span>
                                <h3>{{ $post->title }}</h3>
                                <p class="muted" style="font-size:.92rem">{{ Str::limit($post->excerpt, 110) }}</p>
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         11. BÜLTEN
         =================================================================== --}}
    <section class="section section--tight">
        <div class="wrap">
            <div class="news" data-reveal="up">
                <div>
                    <span class="eyebrow">Bülten</span>
                    <h2 style="margin-block:.7rem .8rem">Yeni koleksiyonlardan ilk siz haberdar olun</h2>
                    <p class="lead" style="font-size:1rem">Ayda en fazla iki e-posta. Sevmezseniz tek tıkla çıkarsınız.</p>
                </div>

                <form class="news__form" method="POST" action="{{ route('inquiry.newsletter') }}">
                    @csrf
                    <label class="sr-only" for="bulten-email">E-posta adresiniz</label>
                    <input class="input" type="email" name="email" id="bulten-email" required
                           placeholder="ornek@eposta.com" autocomplete="email">
                    <button class="btn" type="submit" data-magnetic="0.2">Abone ol <x-ay-icon name="arrow-right" /></button>
                    @error('email')
                        <span class="field__error" style="flex-basis:100%">{{ $message }}</span>
                    @enderror
                </form>
            </div>
        </div>
    </section>

</x-layouts.app>
