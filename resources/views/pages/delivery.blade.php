@php $cutoffHour = (int) setting('same_day_cutoff_hour', 15); @endphp

<x-layouts.app title="Teslimat">

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Teslimat</span>
            <h1 data-reveal="up">Nereye, ne zaman, ne kadara</h1>
            <p class="lead">
                Saat {{ sprintf('%02d:00', $cutoffHour) }}'e kadar verilen siparişleri aynı gün teslim ediyoruz.
                Sonrasındakiler ertesi güne kalır.
            </p>
        </div>
    </header>

    <section class="wrap" style="padding-bottom:var(--sec)">
        <div class="route__zones" data-stagger="70">
            @foreach ($zones as $zone)
                <div class="zone-card">
                    <span class="zone-card__name">{{ $zone->name }}</span>
                    <span class="zone-card__fee">{{ (float) $zone->fee > 0 ? money($zone->fee) : 'Ücretsiz' }}</span>
                    <span class="zone-card__note">
                        {{ $zone->same_day ? 'Aynı gün teslim' : 'Ertesi gün teslim' }}
                        @if ($zone->free_over) · {{ money($zone->free_over) }} üzeri ücretsiz @endif
                    </span>
                    @if ($zone->note)
                        <span class="zone-card__note">{{ $zone->note }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="trust" style="margin-top:clamp(2.5rem,5vw,4rem)">
            <div class="trust__item">
                <x-ay-icon name="clock" />
                <h3>Aynı gün teslimat</h3>
                <p>{{ sprintf('%02d:00', $cutoffHour) }}'e kadar sipariş verin, bugün kapıda olsun.</p>
            </div>
            <div class="trust__item">
                <x-ay-icon name="leaf" />
                <h3>Siparişten sonra hazırlanır</h3>
                <p>Hazır buket tutmuyoruz; her tasarım o an bağlanır.</p>
            </div>
            <div class="trust__item">
                <x-ay-icon name="gift" />
                <h3>Elle yazılan kart</h3>
                <p>Kart notunuz elle yazılır ve buketin yanına iliştirilir.</p>
            </div>
            <div class="trust__item">
                <x-ay-icon name="shield" />
                <h3>Tazelik garantisi</h3>
                <p>Bir sorun olursa 24 saat içinde yenisini gönderiyoruz.</p>
            </div>
        </div>
    </section>

    @if ($faqs->isNotEmpty())
        <section class="section section--sand">
            <div class="wrap wrap--narrow">
                <span class="eyebrow">Sorular</span>
                <h2 style="margin-block:.7rem 2rem" data-reveal="up">Teslimatla ilgili merak edilenler</h2>

                <div class="acc" data-accordion>
                    @foreach ($faqs as $i => $faq)
                        <div class="acc__item {{ $i === 0 ? 'is-open' : '' }}" data-accordion-item>
                            <button class="acc__trigger" type="button" data-accordion-trigger
                                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                                {{ $faq->question }}<span class="acc__sign" aria-hidden="true"></span>
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

</x-layouts.app>
