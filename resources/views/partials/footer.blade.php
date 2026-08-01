<footer class="footer grain">
    <div class="wrap footer__grid">
        <div>
            <a class="brand" href="{{ route('home') }}" style="color:var(--paper)">
                <x-logo class="brand__mark" />
                <span>
                    <span class="brand__name">{{ setting('shop_name', 'Ay Parçası') }}</span>
                    <span class="brand__tag">{{ setting('tagline', 'Hediyelik Tasarımlar & Çiçekçi') }}</span>
                </span>
            </a>

            <p style="margin-top:1.1rem;max-width:34ch;color:color-mix(in srgb, var(--paper) 68%, transparent);font-size:.92rem">
                {{ setting('footer_text', 'Kıbrıs\'ta el yapımı buketler, orkideler ve hediyelik tasarımlar. Her buket dükkânımızda, sipariş üzerine hazırlanır.') }}
            </p>

            <div class="footer__social" style="margin-top:1.5rem">
                @if (setting('instagram'))
                    <a href="{{ setting('instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"><x-ay-icon name="instagram" /></a>
                @endif
                @if (setting('facebook'))
                    <a href="{{ setting('facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><x-ay-icon name="facebook" /></a>
                @endif
                <a href="{{ wa_link() }}" target="_blank" rel="noopener" aria-label="WhatsApp"><x-ay-icon name="whatsapp" :filled="true" /></a>
            </div>
        </div>

        <div>
            <h2 class="footer__title">Alışveriş</h2>
            <ul class="footer__list">
                <li><a href="{{ route('shop.index') }}">Tüm Ürünler</a></li>
                @foreach ($navCategories->take(5) as $cat)
                    <li><a href="{{ route('shop.category', $cat->slug) }}">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="footer__title">Kurumsal</h2>
            <ul class="footer__list">
                <li><a href="{{ route('page.about') }}">Hakkımızda</a></li>
                <li><a href="{{ route('page.delivery') }}">Teslimat & Bölgeler</a></li>
                <li><a href="{{ route('page.faq') }}">Sıkça Sorulan Sorular</a></li>
                <li><a href="{{ route('page.blog') }}">Günlük</a></li>
                <li><a href="{{ route('order.lookup') }}">Sipariş Sorgula</a></li>
                <li><a href="{{ route('page.contact') }}">İletişim</a></li>
            </ul>
        </div>

        <div>
            <h2 class="footer__title">İletişim</h2>
            <ul class="footer__list">
                @if (setting('phone'))
                    <li><a href="tel:{{ preg_replace('/\s+/', '', setting('phone')) }}">{{ setting('phone') }}</a></li>
                @endif
                @if (setting('email'))
                    <li><a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>
                @endif
                @if (setting('address'))
                    <li style="color:color-mix(in srgb, var(--paper) 72%, transparent)">{{ setting('address') }}</li>
                @endif
                @if (setting('hours'))
                    <li style="color:color-mix(in srgb, var(--paper) 72%, transparent)">{{ setting('hours') }}</li>
                @endif
            </ul>
        </div>
    </div>

    {{-- Sayfanın sonunda dolan dev marka adı --}}
    <div class="wrap" data-scrub data-scrub-range="enter" style="padding-bottom:1rem">
        <span class="footer__wordmark">{{ setting('shop_name', 'Ay Parçası') }}</span>
    </div>

    <div class="wrap">
        <div class="footer__bottom">
            <span>© {{ date('Y') }} {{ setting('shop_name', 'Ay Parçası') }}. Tüm hakları saklıdır.</span>
            <span>Kıbrıs · Aynı gün teslimat</span>
        </div>
    </div>
</footer>
