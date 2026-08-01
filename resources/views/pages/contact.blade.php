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
                göndereceğinizi söyleyin, size birkaç öneri hazırlayalım.
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

</x-layouts.app>
