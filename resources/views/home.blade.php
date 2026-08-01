@php
    $heroImage = img_url(setting('hero_image'), 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=2000&q=72');
    $cutoffHour = (int) setting('same_day_cutoff_hour', 15);
    $cutoffMinutes = $cutoffHour * 60;

    // Kart notu bölümü için hazır öneriler
    $suggestions = [
        'Doğum günün kutlu olsun, en güzel yılın olsun.',
        'Seni seviyorum. Her günün böyle güzel olsun.',
        'Nice mutlu yıllara. İyi ki varsınız.',
        'Geçmiş olsun, bir an önce iyileş.',
    ];

    // Teslimat rotası düğüm konumları (SVG koordinatı)
    $nodeMap = [
        'Lefke' => [70, 128],
        'Güzelyurt' => [190, 104],
        'Girne' => [372, 62],
        'Lefkoşa' => [468, 122],
        'İskele' => [672, 84],
        'Mağusa' => [742, 140],
    ];
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
                        <span class="eyebrow">Nereden başlayalım</span>
                        <h2 data-reveal="up">Çiçeği sebebiyle seçin</h2>
                    </div>
                    <p class="lead" style="max-width:34ch" data-reveal="up">
                        Doğum günü mü, özür mü, yeni bir başlangıç mı — hangisi olduğunu söyleyin, gerisini biz düşünelim.
                    </p>
                </div>
            </div>

            <div class="wrap wrap--wide">
                <div class="panels" data-panels data-swap data-reveal="fade">
                    @foreach ($occasions->take(6) as $i => $cat)
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
         5. SPOTLIGHT — yapışkan görsel, yanından geçen üç adım
         =================================================================== --}}
    @if ($spotlight && $spotlightProducts->isNotEmpty())
        @php
            $spotImages = $spotlightProducts->map(fn ($p) => img_url($p->hero_image))->filter()->values();
        @endphp

        <section class="section section--sand spot" data-scrub data-scrub-range="cover" data-swap>
            <div class="wrap spot__grid">
                <div class="spot__media">
                    @foreach ($spotImages as $i => $src)
                        <img src="{{ $src }}" alt="" loading="lazy" decoding="async"
                             class="{{ $i === 0 ? 'is-active' : '' }}" data-swap-item>
                    @endforeach
                </div>

                <div class="spot__steps">
                    <div class="spot__step is-active">
                        <span class="spot__no">01 — Seçim</span>
                        <h3>Her dal tek tek elden geçer</h3>
                        <p class="lead">
                            {{ $spotlight->name }} koleksiyonundaki her sap, sabah gelen partiden ayıklanır.
                            Açmamış olan raftan çıkar; size en uzun ömürlüsü kalır.
                        </p>
                    </div>

                    <div class="spot__step">
                        <span class="spot__no">02 — Tasarım</span>
                        <h3>Buket, siparişten sonra bağlanır</h3>
                        <p class="lead">
                            Hazır buket tutmuyoruz. Sipariş düştüğünde tezgâha çıkıyor, elde bağlanıyor,
                            kâğıdı ve kurdelesi o an seçiliyor.
                        </p>
                    </div>

                    <div class="spot__step">
                        <span class="spot__no">03 — Teslim</span>
                        <h3>Kapıya suyla birlikte gider</h3>
                        <p class="lead">
                            Su haznesiyle paketlenir; yolda susamaz. Teslimden sonra alıcıya ulaştığına dair
                            fotoğraflı bilgi göndeririz.
                        </p>
                        <a class="btn btn--ghost" style="margin-top:1.4rem" href="{{ route('shop.category', $spotlight->slug) }}">
                            {{ $spotlight->name }} koleksiyonu <x-ay-icon name="arrow-right" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         6. MOZAİK HALKA — logonun halkası, kaydırdıkça döner
         =================================================================== --}}
    <section class="section ring grain" data-scrub data-scrub-range="enter">
        <div class="wrap ring__grid">
            <div class="ring__list">
                <div class="ring__item" data-reveal="up">
                    <span class="ring__stat"><span data-count-to="{{ $stats['orders'] }}">0</span>+</span>
                    <h3>Teslim edilen sipariş</h3>
                    <p>Girne'den Mağusa'ya, kapı kapı.</p>
                </div>
                <div class="ring__item" data-reveal="up">
                    <span class="ring__stat"><span data-count-to="{{ $stats['products'] }}">0</span></span>
                    <h3>Tasarım</h3>
                    <p>Buket, aranjman, orkide ve hediyelik.</p>
                </div>
            </div>

            <div class="ring__art" data-reveal="scale">
                {{-- Mozaik halka — logodaki üçgen dizisi --}}
                <svg class="ring__svg" viewBox="0 0 200 200" aria-hidden="true">
                    <g>
                        @for ($i = 0; $i < 36; $i++)
                            @php $c = $i % 3 === 0 ? 'var(--sun)' : ($i % 3 === 1 ? 'var(--turq)' : 'var(--sea)'); @endphp
                            <polygon
                                points="100,6 105,20 95,20"
                                fill="{{ $c }}"
                                transform="rotate({{ $i * 10 }} 100 100)"
                                opacity="{{ $i % 2 ? 0.95 : 0.6 }}"
                            />
                        @endfor
                    </g>
                    <circle cx="100" cy="100" r="76" fill="none" stroke="var(--turq)" stroke-width="1" opacity=".35" />
                </svg>

                <div class="ring__core">
                    <span>Elde<br>bağlanır</span>
                </div>
            </div>

            <div class="ring__list">
                <div class="ring__item" data-reveal="up">
                    <span class="ring__stat"><span data-count-to="{{ $stats['zones'] }}">0</span></span>
                    <h3>Teslimat bölgesi</h3>
                    <p>Ada genelinde, çoğunda aynı gün.</p>
                </div>
                <div class="ring__item" data-reveal="up">
                    <span class="ring__stat">7/7</span>
                    <h3>Açığız</h3>
                    <p>WhatsApp'tan her gün ulaşabilirsiniz.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================================================================
         7. TESLİMAT ROTASI — SVG kaydırdıkça çizilir
         =================================================================== --}}
    @if ($zones->isNotEmpty())
        <section class="section route" data-scrub data-scrub-range="enter">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Nereye götürelim</span>
                        <h2 data-reveal="up">Adanın her yerine gidiyoruz</h2>
                    </div>
                    <p class="lead" style="max-width:32ch" data-reveal="up">
                        Bölgenizi seçtiğinizde teslimat ücreti ve en erken teslim günü anında görünür.
                    </p>
                </div>

                <div class="route__art">
                    <svg class="route__svg" viewBox="0 0 820 200" aria-hidden="true">
                        <path class="route__ghost"
                              d="M40 150 C 140 60, 260 40, 380 70 S 560 150, 680 90 S 780 60, 800 96" />
                        <path class="route__line" data-draw
                              d="M40 150 C 140 60, 260 40, 380 70 S 560 150, 680 90 S 780 60, 800 96" />

                        @foreach ($zones as $i => $zone)
                            @php
                                $pos = $nodeMap[$zone->name] ?? [80 + $i * (700 / max(1, $zones->count())), 110];
                            @endphp
                            <g>
                                <circle cx="{{ $pos[0] }}" cy="{{ $pos[1] }}" r="6"
                                        fill="{{ $zone->same_day ? 'var(--sun)' : 'var(--ink-3)' }}" />
                                <text x="{{ $pos[0] }}" y="{{ $pos[1] - 14 }}" text-anchor="middle"
                                      font-size="13" font-weight="700" fill="var(--ink)"
                                      font-family="Manrope, sans-serif">{{ $zone->name }}</text>
                            </g>
                        @endforeach
                    </svg>
                </div>

                <div class="route__zones" data-stagger="70">
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
         8. YENİ GELENLER
         =================================================================== --}}
    @if ($newest->isNotEmpty())
        <section class="section section--tight">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Tezgâhtan yeni çıktı</span>
                        <h2 data-reveal="up">Bu haftanın tasarımları</h2>
                    </div>
                    <a class="link-u" href="{{ route('shop.index', ['sirala' => 'yeni']) }}">
                        Yeni gelenler <x-ay-icon name="arrow-right" />
                    </a>
                </div>

                <div class="grid-products">
                    @foreach ($newest->take(4) as $product)
                        <x-product-card :product="$product" reveal="wipe" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         9. KART NOTU — canlı yazan kart
         =================================================================== --}}
    <section class="section note" data-scrub data-scrub-range="enter">
        <div class="wrap note__grid">
            <div>
                <span class="eyebrow">Kart notu</span>
                <h2 style="margin-block:.8rem 1.1rem" data-reveal="up">
                    Asıl hediye, <span class="serif-em">yazdığınız cümle</span>
                </h2>
                <p class="lead">
                    Her siparişe elle yazılmış bir kart ekliyoruz. Şimdi deneyin — yazdıkça kartta görün.
                </p>

                <div class="field" style="margin-top:1.5rem;max-width:30rem">
                    <label for="kart-notu">Notunuz</label>
                    <textarea class="textarea" id="kart-notu" maxlength="200"
                              placeholder="Kalbinizden geçeni yazın…" data-card-input></textarea>
                    <span class="field__hint">En fazla 200 karakter. Siparişte de düzenleyebilirsiniz.</span>
                </div>

                <div class="note__chips">
                    @foreach ($suggestions as $s)
                        <button type="button" class="chip" data-card-suggestion="{{ $s }}">{{ Str::limit($s, 26) }}</button>
                    @endforeach
                </div>
            </div>

            <div data-parallax="-0.06">
                <div class="note__card">
                    <p class="note__text is-empty" data-card-out
                       data-placeholder="Buraya yazdıklarınız, kartın üzerinde tam olarak böyle görünecek.">
                        Buraya yazdıklarınız, kartın üzerinde tam olarak böyle görünecek.
                    </p>
                    <span class="note__sign">{{ setting('shop_name', 'Ay Parçası') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================================================================
         10. GALERİ KOLONLARI — farklı hızlarda kayar
         =================================================================== --}}
    @if ($gallery->count() >= 6)
        @php $chunks = $gallery->chunk(ceil($gallery->count() / 3)); @endphp

        <section class="section cols" data-scrub data-scrub-range="enter">
            <div class="wrap" style="margin-bottom:clamp(2rem,4vw,3rem)">
                <div class="section-head" style="margin-bottom:0">
                    <div class="section-head__text">
                        <span class="eyebrow">Dükkândan</span>
                        <h2 data-reveal="up">Geçen hafta yolladıklarımız</h2>
                    </div>
                    <a class="link-u" href="{{ setting('instagram', '#') }}" target="_blank" rel="noopener">
                        Instagram'da takip et <x-ay-icon name="arrow-right" />
                    </a>
                </div>
            </div>

            <div class="wrap wrap--wide">
                <div class="cols__grid">
                    @foreach ($chunks as $i => $chunk)
                        <div class="cols__col" data-parallax="{{ [-0.18, 0.12, -0.28][$i] ?? 0 }}">
                            @foreach ($chunk as $src)
                                <figure><img src="{{ img_url($src) }}" alt="" loading="lazy" decoding="async"></figure>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================================================================
         11. YORUMLAR — tek büyük alıntı, kaydırdıkça değişir
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
         12. SSS
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
         13. GÜNLÜK
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
         14. BÜLTEN
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
