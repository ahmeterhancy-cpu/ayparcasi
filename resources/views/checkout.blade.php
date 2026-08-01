@php
    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();
    $cutoffHour = (int) setting('same_day_cutoff_hour', 15);
    $pastCutoff = now()->hour >= $cutoffHour;
    $defaultDate = old('delivery_date', $pastCutoff ? $tomorrow : $today);

    $suggestions = [
        'Doğum günün kutlu olsun, en güzel yılın olsun.',
        'Seni seviyorum.',
        'Geçmiş olsun, bir an önce iyileş.',
        'Tebrikler! Nice başarılara.',
    ];
@endphp

<x-layouts.app title="Siparişi tamamla">

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Son adım</span>
            <h1 data-reveal="up">Siparişi tamamlayalım</h1>
            <p class="lead">Kime, nereye ve ne zaman gideceğini söyleyin. Kart notunu da buradan yazabilirsiniz.</p>
        </div>
    </header>

    @guest
        <div class="wrap">
            <div class="checkout-login">
                <span><strong>Hesabınız var mı?</strong> Giriş yaparsanız bilgileriniz ve adresiniz hazır gelir.</span>
                <span class="checkout-login__actions">
                    <a class="btn btn--rect btn--sm" href="{{ route('login') }}">Giriş yap</a>
                    <a class="link-u" href="{{ route('register') }}">Hesap oluştur</a>
                </span>
            </div>
        </div>
    @endguest

    @if ($errors->any())
        <div class="wrap">
            <div class="alert">
                Formda eksik ya da hatalı alanlar var:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}"
          data-checkout
          data-subtotal="{{ $summary['subtotal'] }}"
          data-discount="{{ $summary['discount'] }}"
          data-free-delivery="{{ $summary['coupon']?->free_delivery ? '1' : '0' }}">
        @csrf

        <div class="wrap checkout">
            <div>
                {{-- 1. Sipariş veren --}}
                <section class="step">
                    <div class="step__head">
                        <span class="step__no">01</span>
                        <h2>Sizin bilgileriniz</h2>
                    </div>

                    <div class="grid-2">
                        <div class="field">
                            <label for="customer_name">Adınız soyadınız *</label>
                            <input class="input @error('customer_name') is-error @enderror" type="text"
                                   name="customer_name" id="customer_name" required autocomplete="name"
                                   value="{{ old('customer_name', $user?->name) }}">
                            @error('customer_name')<span class="field__error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="customer_phone">Telefonunuz *</label>
                            <input class="input @error('customer_phone') is-error @enderror" type="tel"
                                   name="customer_phone" id="customer_phone" required autocomplete="tel"
                                   placeholder="0533 000 00 00" value="{{ old('customer_phone', $user?->phone) }}">
                            @error('customer_phone')<span class="field__error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="field" style="margin-top:.9rem">
                        <label for="customer_email">E-posta (isteğe bağlı)</label>
                        <input class="input" type="email" name="customer_email" id="customer_email"
                               autocomplete="email" value="{{ old('customer_email', $user?->email) }}">
                        <span class="field__hint">Sipariş özetini e-postayla da gönderelim.</span>
                    </div>
                </section>

                {{-- 2. Teslimat --}}
                <section class="step">
                    <div class="step__head">
                        <span class="step__no">02</span>
                        <h2>Nereye gidecek?</h2>
                    </div>

                    @if ($savedAddresses->isNotEmpty())
                        <div class="saved-addresses" data-saved-addresses>
                            <span class="label" style="display:block;margin-bottom:.55rem">Kayıtlı adreslerinizden seçin</span>

                            <div class="saved-addresses__list">
                                @foreach ($savedAddresses as $saved)
                                    @php
                                        // NOT: @json'a çok satırlı dizi yazılmaz — değer burada hazırlanır
                                        $fill = [
                                            'recipient_name' => $saved->recipient_name,
                                            'recipient_phone' => $saved->recipient_phone,
                                            'delivery_zone_id' => $saved->delivery_zone_id,
                                            'delivery_address' => $saved->address,
                                        ];
                                    @endphp
                                    <button type="button" class="saved-address" data-fill="{{ json_encode($fill, JSON_UNESCAPED_UNICODE) }}">
                                        <strong>{{ $saved->title }}</strong>
                                        @if ($saved->is_default)
                                            <span class="badge badge--turq">Varsayılan</span>
                                        @endif
                                        <span class="muted">{{ Str::limit($saved->summary, 60) }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <p class="field__hint" style="margin-top:.5rem">
                                Seçtiğinizde aşağıdaki alanlar dolar; dilerseniz düzenleyebilirsiniz.
                            </p>
                        </div>
                    @endif

                    <div class="grid-2">
                        <div class="field">
                            <label for="recipient_name">Alıcının adı *</label>
                            <input class="input @error('recipient_name') is-error @enderror" type="text"
                                   name="recipient_name" id="recipient_name" required
                                   value="{{ old('recipient_name') }}">
                            @error('recipient_name')<span class="field__error">{{ $message }}</span>@enderror
                        </div>

                        <div class="field">
                            <label for="recipient_phone">Alıcının telefonu</label>
                            <input class="input" type="tel" name="recipient_phone" id="recipient_phone"
                                   value="{{ old('recipient_phone') }}">
                            <span class="field__hint">Adres bulunamazsa arayabilelim.</span>
                        </div>
                    </div>

                    <div style="margin-top:1.25rem">
                        <span class="label" style="display:block;margin-bottom:.55rem">Bölge *</span>
                        <div class="zones">
                            @foreach ($zones as $i => $zone)
                                <label class="choice">
                                    <input type="radio" name="delivery_zone_id" value="{{ $zone->id }}"
                                           data-fee="{{ (float) $zone->fee }}"
                                           data-free-over="{{ $zone->free_over !== null ? (float) $zone->free_over : '' }}"
                                           data-same-day="{{ $zone->same_day ? '1' : '0' }}"
                                           data-name="{{ $zone->name }}"
                                           @checked(old('delivery_zone_id', $zones->first()?->id) == $zone->id)>
                                    <span class="choice__dot" aria-hidden="true"></span>
                                    <span class="choice__text">
                                        <span class="choice__title">{{ $zone->name }}</span>
                                        <span class="choice__meta">
                                            {{ (float) $zone->fee > 0 ? money($zone->fee) : 'Ücretsiz' }}
                                            @if ($zone->same_day) · aynı gün @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="sameday-note" data-sameday-note data-state="ok"></p>
                        @error('delivery_zone_id')<span class="field__error">{{ $message }}</span>@enderror
                    </div>

                    <div class="field" style="margin-top:1.25rem">
                        <label for="delivery_address">Açık adres *</label>
                        <textarea class="textarea @error('delivery_address') is-error @enderror"
                                  name="delivery_address" id="delivery_address" required
                                  placeholder="Sokak, bina, daire, tarif…">{{ old('delivery_address') }}</textarea>
                        @error('delivery_address')<span class="field__error">{{ $message }}</span>@enderror
                    </div>

                    @auth
                        <div style="margin-top:.9rem">
                            <label class="choice choice--check">
                                <input type="checkbox" name="save_address" value="1" data-save-address
                                       @checked(old('save_address'))>
                                <span class="choice__dot" aria-hidden="true"></span>
                                <span class="choice__text">
                                    <span class="choice__title">Bu adresi defterime kaydet</span>
                                    <span class="choice__meta">Bir dahaki sefere tek tıkla seçebilirsiniz.</span>
                                </span>
                            </label>

                            <div class="field" data-address-title hidden style="margin-top:.6rem;max-width:22rem">
                                <label for="address_title">Adres başlığı</label>
                                <input class="input" type="text" name="address_title" id="address_title"
                                       maxlength="60" placeholder="Ev, Ofis, Annem…"
                                       value="{{ old('address_title') }}">
                            </div>
                        </div>
                    @endauth

                    <div class="grid-2" style="margin-top:.9rem">
                        <div class="field">
                            <label for="delivery_date">Teslim tarihi *</label>
                            <input class="input @error('delivery_date') is-error @enderror" type="date"
                                   name="delivery_date" id="delivery_date" required
                                   min="{{ $today }}"
                                   data-today="{{ $today }}" data-tomorrow="{{ $tomorrow }}"
                                   value="{{ $defaultDate }}">
                            @error('delivery_date')<span class="field__error">{{ $message }}</span>@enderror
                            @if ($pastCutoff)
                                <span class="field__hint">
                                    Bugünün siparişleri saat {{ sprintf('%02d:00', $cutoffHour) }}'te kapandı.
                                </span>
                            @endif
                        </div>

                        @if ($slots->isNotEmpty())
                            <div class="field">
                                <label for="delivery_slot">Saat aralığı</label>
                                <select class="select" name="delivery_slot" id="delivery_slot">
                                    <option value="">Fark etmez</option>
                                    @foreach ($slots as $slot)
                                        <option value="{{ $slot->label }}" @selected(old('delivery_slot') === $slot->label)>
                                            {{ $slot->label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- 3. Kart notu --}}
                <section class="step">
                    <div class="step__head">
                        <span class="step__no">03</span>
                        <h2>Kart notu</h2>
                    </div>

                    <div class="field">
                        <label for="card_message">Karta ne yazalım?</label>
                        <textarea class="textarea" name="card_message" id="card_message" maxlength="400"
                                  placeholder="Elle yazıp buketin yanına iliştiriyoruz."
                                  data-card-input>{{ old('card_message') }}</textarea>
                    </div>

                    <div class="note__chips">
                        @foreach ($suggestions as $s)
                            <button type="button" class="chip" data-card-suggestion="{{ $s }}">{{ Str::limit($s, 26) }}</button>
                        @endforeach
                    </div>

                    <div class="grid-2" style="margin-top:1.1rem">
                        <div class="field">
                            <label for="card_sender">Kartta gönderen adı</label>
                            <input class="input" type="text" name="card_sender" id="card_sender"
                                   value="{{ old('card_sender') }}" placeholder="Örn. Ayşe & Mehmet">
                        </div>

                        <label class="choice choice--check" style="align-self:end">
                            <input type="checkbox" name="hide_sender" value="1" @checked(old('hide_sender'))>
                            <span class="choice__dot" aria-hidden="true"></span>
                            <span class="choice__text">
                                <span class="choice__title">İsmim yazmasın</span>
                                <span class="choice__meta">Sürpriz olsun.</span>
                            </span>
                        </label>
                    </div>

                    <div class="field" style="margin-top:.9rem">
                        <label for="note">Bize not (isteğe bağlı)</label>
                        <textarea class="textarea" name="note" id="note" style="min-height:5rem"
                                  placeholder="Renk tercihi, kapı kodu, teslim saati…">{{ old('note') }}</textarea>
                    </div>
                </section>

                {{-- 4. Ödeme --}}
                <section class="step">
                    <div class="step__head">
                        <span class="step__no">04</span>
                        <h2>Ödeme</h2>
                    </div>

                    <div class="methods">
                        @if ($tikoEnabled)
                            <label class="choice">
                                <input type="radio" name="payment_method" value="tiko" @checked(old('payment_method', 'tiko') === 'tiko')>
                                <span class="choice__dot" aria-hidden="true"></span>
                                <span class="choice__text">
                                    <span class="choice__title">Kredi / banka kartı</span>
                                    <span class="choice__meta">3D Secure ile güvenli ödeme (Tiko).</span>
                                </span>
                                <x-ay-icon name="card" style="width:22px;height:22px;color:var(--sea);margin-left:auto" />
                            </label>
                        @endif

                        <label class="choice">
                            <input type="radio" name="payment_method" value="whatsapp"
                                   @checked(old('payment_method', $tikoEnabled ? 'tiko' : 'whatsapp') === 'whatsapp')>
                            <span class="choice__dot" aria-hidden="true"></span>
                            <span class="choice__text">
                                <span class="choice__title">WhatsApp ile tamamla</span>
                                <span class="choice__meta">Siparişi kaydedelim, ödemeyi WhatsApp'tan konuşalım.</span>
                            </span>
                            <x-ay-icon name="whatsapp" :filled="true" style="width:22px;height:22px;color:#128c7e;margin-left:auto" />
                        </label>

                        <label class="choice">
                            <input type="radio" name="payment_method" value="cash" @checked(old('payment_method') === 'cash')>
                            <span class="choice__dot" aria-hidden="true"></span>
                            <span class="choice__text">
                                <span class="choice__title">Kapıda ödeme</span>
                                <span class="choice__meta">Nakit ya da kart, teslimatta.</span>
                            </span>
                            <x-ay-icon name="cash" style="width:22px;height:22px;color:var(--sea);margin-left:auto" />
                        </label>

                        <label class="choice">
                            <input type="radio" name="payment_method" value="transfer" @checked(old('payment_method') === 'transfer')>
                            <span class="choice__dot" aria-hidden="true"></span>
                            <span class="choice__text">
                                <span class="choice__title">Havale / EFT</span>
                                <span class="choice__meta">Hesap bilgilerini sipariş sonrası paylaşırız.</span>
                            </span>
                            <x-ay-icon name="bank" style="width:22px;height:22px;color:var(--sea);margin-left:auto" />
                        </label>
                    </div>

                    @error('payment_method')<span class="field__error">{{ $message }}</span>@enderror

                    <div class="method-note" data-method-note="tiko" hidden>
                        Kart bilgileriniz bize hiç ulaşmaz; ödeme bankanın 3D Secure sayfasında tamamlanır.
                    </div>
                    <div class="method-note" data-method-note="whatsapp" hidden>
                        Siparişiniz kaydedilir ve WhatsApp penceresi açılır. Detayları oradan konuşup onaylıyoruz.
                    </div>
                    <div class="method-note" data-method-note="cash" hidden>
                        Kurye teslimatta tahsil eder. Kartla ödeyecekseniz lütfen notta belirtin.
                    </div>
                    <div class="method-note" data-method-note="transfer" hidden>
                        {{ setting('bank_details', 'Sipariş sonrası hesap bilgilerini WhatsApp\'tan paylaşırız.') }}
                    </div>

                    <label class="choice choice--check" style="margin-top:1.25rem">
                        <input type="checkbox" name="kvkk" value="1" required @checked(old('kvkk'))>
                        <span class="choice__dot" aria-hidden="true"></span>
                        <span class="choice__text">
                            <span class="choice__title">Aydınlatma metnini okudum, onaylıyorum *</span>
                            <span class="choice__meta">Bilgileriniz yalnızca siparişin teslimi için kullanılır.</span>
                        </span>
                    </label>
                    @error('kvkk')<span class="field__error">{{ $message }}</span>@enderror
                </section>
            </div>

            {{-- Özet --}}
            <aside class="summary">
                <h2 style="font-size:1.15rem">Siparişiniz</h2>

                <div style="display:grid;gap:.75rem;padding-bottom:.9rem;border-bottom:1px solid var(--line)">
                    @foreach ($lines as $line)
                        <div style="display:flex;gap:.75rem;align-items:flex-start">
                            <span style="flex:1;font-size:.88rem">
                                <strong>{{ $line['quantity'] }}×</strong> {{ $line['product']->name }}
                                @if ($line['variant'])
                                    <span class="muted">({{ $line['variant']->name }})</span>
                                @endif
                            </span>
                            <span style="font-weight:600;font-variant-numeric:tabular-nums;white-space:nowrap">
                                {{ money($line['line_total']) }}
                            </span>
                        </div>
                    @endforeach
                </div>

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
                    <span data-fee-out>—</span>
                </div>

                <div class="summary__row summary__row--total">
                    <span>Toplam</span>
                    <span data-total-out>{{ money($summary['total']) }}</span>
                </div>

                <button class="btn btn--block btn--lg" type="submit" data-magnetic="0.14">
                    Siparişi onayla <x-ay-icon name="arrow-right" />
                </button>

                <p class="muted" style="font-size:.8rem;text-align:center">
                    Onayladıktan sonra siparişinizi WhatsApp'tan takip edebilirsiniz.
                </p>
            </aside>
        </div>
    </form>

</x-layouts.app>
