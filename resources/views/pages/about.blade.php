<x-layouts.app title="Hakkımızda">

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Hakkımızda</span>
            <h1 data-reveal="up" data-split="words">{{ setting('about_title', 'Küçük bir dükkân, uzun bir alışkanlık') }}</h1>
        </div>
    </header>

    <section class="wrap about-grid" style="padding-bottom:var(--sec)">
        <div class="prose">
            {!! nl2br(e(setting('about_text', "Ay Parçası, hediyeliğin de çiçeğin de aynı özenle seçilmesi gerektiğine inanan küçük bir dükkân.\n\nHazır buket tutmuyoruz. Sipariş düştüğünde tezgâha çıkıyor, elde bağlanıyor; kâğıdı, kurdelesi ve kart notu o an seçiliyor. Bu yüzden iki buket asla birebir aynı olmuyor.\n\nAdımız bir iltifattan geliyor: Kıbrıs'ta sevdiğinize \"ay parçası\" dersiniz. Gönderdiğiniz çiçeğin de aynı şeyi söylemesini istiyoruz."))) !!}

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:2rem">
                <a class="btn" href="{{ route('shop.index') }}">Tasarımları gör <x-ay-icon name="arrow-right" /></a>
                <a class="btn btn--wa" href="{{ wa_link('Merhaba!') }}" target="_blank" rel="noopener">
                    <x-ay-icon name="whatsapp" :filled="true" /> Bize yazın
                </a>
            </div>
        </div>

        <div class="about-media" data-reveal="mask">
            <img src="{{ img_url(setting('about_image'), 'https://images.unsplash.com/photo-1520763185298-1b434c919102?auto=format&fit=crop&w=1000&q=72') }}"
                 alt="" loading="lazy" decoding="async">
        </div>
    </section>

    @if ($testimonials->isNotEmpty())
        <section class="section section--sand">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Müşterilerimiz</span>
                        <h2 data-reveal="up">Ne diyorlar?</h2>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(17rem,1fr));gap:1.25rem" data-stagger="80">
                    @foreach ($testimonials as $t)
                        <figure style="padding:1.5rem;background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--line);display:grid;gap:.8rem">
                            <span class="stars" aria-label="{{ $t->rating }} yıldız">
                                @for ($s = 0; $s < $t->rating; $s++)<x-ay-icon name="star" :filled="true" />@endfor
                            </span>
                            <p style="font-family:var(--font-display);font-size:1.08rem;line-height:1.4">“{{ $t->body }}”</p>
                            <figcaption class="muted" style="font-size:.85rem">
                                {{ $t->name }}@if ($t->city) · {{ $t->city }}@endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.app>
