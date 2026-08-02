<x-layouts.app title="İletişim">

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">İletişim</span>
            <h1 data-reveal="up">Bize ulaşın</h1>
            <p class="lead">En hızlı yanıtı WhatsApp'tan alırsınız — genelde birkaç dakika içinde dönüyoruz.</p>
        </div>
    </header>

    <section class="wrap contact-grid" style="padding-bottom:var(--sec)">
        <dl class="contact-list">
            @if (setting('phone'))
                <div>
                    <dt>Telefon</dt>
                    <dd><a href="tel:{{ preg_replace('/\s+/', '', setting('phone')) }}">{{ setting('phone') }}</a></dd>
                </div>
            @endif

            <div>
                <dt>WhatsApp</dt>
                <dd><a href="{{ wa_link('Merhaba!') }}" target="_blank" rel="noopener">Mesaj gönder</a></dd>
            </div>

            @if (setting('email'))
                <div>
                    <dt>E-posta</dt>
                    <dd><a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></dd>
                </div>
            @endif

            @if (setting('address'))
                <div>
                    <dt>Adres</dt>
                    <dd style="font-size:1.05rem;line-height:1.5">{{ setting('address') }}</dd>
                </div>
            @endif

            @if (setting('hours'))
                <div>
                    <dt>Çalışma saatleri</dt>
                    <dd style="font-size:1.05rem">{{ setting('hours') }}</dd>
                </div>
            @endif
        </dl>

        <div style="padding:clamp(1.5rem,3vw,2.5rem);background:var(--turq-3);border-radius:var(--radius-lg)">
            <h2 style="font-size:1.5rem">Sipariş vermek için</h2>
            <p style="margin-top:.7rem;color:var(--ink-2)">
                Siteden seçip kartla ödeyebilirsiniz. Kararsızsanız WhatsApp'tan yazın; kime, hangi vesileyle
                göndereceğinizi söyleyin, size birkaç öneri sunalım.
            </p>

            <div style="display:grid;gap:.6rem;margin-top:1.5rem">
                <a class="btn btn--wa btn--block" href="{{ wa_link('Merhaba, sipariş vermek istiyorum.') }}"
                   target="_blank" rel="noopener">
                    <x-ay-icon name="whatsapp" :filled="true" /> WhatsApp'tan sipariş ver
                </a>
                <a class="btn btn--ghost btn--block" href="{{ route('shop.index') }}">Ürünlere göz at</a>
            </div>
        </div>
    </section>

    {{-- Harita — koordinat girilmemişse bölüm hiç görünmez --}}
    @php
        $lat = setting('map_lat');
        $lng = setting('map_lng');
    @endphp

    @if ($lat && $lng)
        @php
            // OpenStreetMap gömme: anahtar istemiyor, çerez bırakmıyor.
            $d = 0.0025; // haritanın kapsayacağı alan
            $bbox = ($lng - $d).','.($lat - $d).','.($lng + $d).','.($lat + $d);
            $osm = 'https://www.openstreetmap.org/export/embed.html?bbox='.$bbox.'&layer=mapnik&marker='.$lat.','.$lng;
            $yolTarifi = 'https://www.google.com/maps/dir/?api=1&destination='.$lat.','.$lng;
        @endphp

        <section class="section section--tight" style="padding-top:0">
            <div class="wrap">
                <div class="map-block">
                    {{-- loading="lazy": harita ekrana gelmeden dış istek atılmasın --}}
                    <iframe
                        class="map-block__frame"
                        src="{{ $osm }}"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="{{ setting('shop_name', 'Ay Parçası') }} konumu"
                    ></iframe>

                    <div class="map-block__foot">
                        @if (setting('address'))
                            <p><x-ay-icon name="pin" /> {{ setting('address') }}</p>
                        @endif
                        <a class="btn btn--rect btn--sm" href="{{ $yolTarifi }}" target="_blank" rel="noopener">
                            Yol tarifi al <x-ay-icon name="arrow-right" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

</x-layouts.app>
