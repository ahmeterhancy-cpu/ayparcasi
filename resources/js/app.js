import { initMotion, scheduler } from './motion.js';

/* -------------------------------------------------------------------------
 * Üst bar — kaydırınca zemin kazanır, aşağı inerken gizlenir
 * ---------------------------------------------------------------------- */
function initHeader() {
    const header = document.querySelector('[data-header]');
    if (!header) return;

    let last = 0;

    scheduler.add({
        update(f) {
            header.classList.toggle('is-stuck', f.y > 24);

            // Yalnızca hero'yu geçtikten sonra gizle/göster
            if (f.y > 320) {
                header.classList.toggle('is-hidden', f.y > last && f.dy > 2);
            } else {
                header.classList.remove('is-hidden');
            }

            last = f.y;
            return false;
        },
    });
}

/* -------------------------------------------------------------------------
 * Mobil menü + arama katmanı
 * ---------------------------------------------------------------------- */
function initOverlays() {
    const lock = (on) => document.body.classList.toggle('is-locked', on);

    document.querySelectorAll('[data-toggle]').forEach((btn) => {
        const target = document.querySelector(btn.dataset.toggle);
        if (!target) return;

        btn.addEventListener('click', () => {
            const open = target.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', String(open));
            lock(open);

            if (open) {
                const focusable = target.querySelector('input, button, a');
                focusable && setTimeout(() => focusable.focus(), 60);
            }
        });
    });

    document.querySelectorAll('[data-close]').forEach((btn) => {
        btn.addEventListener('click', () => {
            btn.closest('.overlay')?.classList.remove('is-open');
            lock(false);
            document
                .querySelectorAll('[data-toggle][aria-expanded="true"]')
                .forEach((t) => t.setAttribute('aria-expanded', 'false'));
        });
    });

    addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        // .overlay dışında, data-escapable ile işaretlenmiş açılır panelleri de
        // kapatır (ör. mağaza filtre çekmecesi — kendisi .overlay değil, çünkü
        // masaüstünde normal yan sütun olarak duruyor).
        document
            .querySelectorAll('.overlay.is-open, [data-escapable].is-open')
            .forEach((p) => p.classList.remove('is-open'));
        document
            .querySelectorAll('[data-toggle][aria-expanded="true"]')
            .forEach((t) => t.setAttribute('aria-expanded', 'false'));
        lock(false);
    });
}

/* -------------------------------------------------------------------------
 * Akordiyon (SSS) — yükseklik animasyonu, tek açık
 * ---------------------------------------------------------------------- */
function initAccordions() {
    document.querySelectorAll('[data-accordion]').forEach((group) => {
        const items = [...group.querySelectorAll('[data-accordion-item]')];

        items.forEach((item) => {
            const trigger = item.querySelector('[data-accordion-trigger]');
            const panel = item.querySelector('[data-accordion-panel]');
            if (!trigger || !panel) return;

            const setHeight = (open) => panel.style.setProperty('--h', open ? panel.scrollHeight + 'px' : '0px');

            trigger.addEventListener('click', () => {
                const open = !item.classList.contains('is-open');

                items.forEach((other) => {
                    if (other === item) return;
                    other.classList.remove('is-open');
                    other.querySelector('[data-accordion-panel]')?.style.setProperty('--h', '0px');
                    other.querySelector('[data-accordion-trigger]')?.setAttribute('aria-expanded', 'false');
                });

                item.classList.toggle('is-open', open);
                trigger.setAttribute('aria-expanded', String(open));
                setHeight(open);
            });

            // Sayfa açılışında zaten açık olan öğenin yüksekliğini ölç
            if (item.classList.contains('is-open')) {
                requestAnimationFrame(() => setHeight(true));
            }

            addEventListener('resize', () => setHeight(item.classList.contains('is-open')), { passive: true });
        });
    });
}

/* -------------------------------------------------------------------------
 * "Ne için çiçek?" — genişleyen panel seçici
 * ---------------------------------------------------------------------- */
function initPanels() {
    document.querySelectorAll('[data-panels]').forEach((group) => {
        const panels = [...group.querySelectorAll('[data-panel]')];
        if (!panels.length) return;

        const activate = (i) => {
            panels.forEach((p, k) => p.classList.toggle('is-active', k === i));
            group.style.setProperty('--active', i);
        };

        panels.forEach((panel, i) => {
            panel.addEventListener('pointerenter', () => activate(i));
            panel.addEventListener('focusin', () => activate(i));
        });

        activate(0);

        // Bölüm ekranda ilerlerken aktif panel de ilerlesin
        group.addEventListener('swap', (e) => activate(e.detail.index));
    });
}

/* -------------------------------------------------------------------------
 * Ürün galerisi + boy seçimi + canlı fiyat
 * ---------------------------------------------------------------------- */
function initGallery() {
    const gallery = document.querySelector('[data-gallery]');
    if (!gallery) return;

    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            thumbs.forEach((t) => t.classList.remove('is-active'));
            thumb.classList.add('is-active');

            const src = thumb.dataset.galleryThumb;
            if (!main || main.src === src) return;

            main.classList.add('is-swapping');
            const img = new Image();
            img.onload = () => {
                main.src = src;
                requestAnimationFrame(() => main.classList.remove('is-swapping'));
            };
            img.src = src;
        });
    });
}

/**
 * Boy seçimi + adet + canlı fiyat.
 * `root` verilebilir; hızlı bakış penceresi kendi kökünü geçirir.
 */
function initProduct(root = document) {
    const form = root.querySelector('[data-product-form]');
    if (!form) return;

    // Fiyat ve stok göstergeleri formun dışında (başlık bloğunda) duruyor
    const priceOut = root.querySelector('[data-price-out]');
    const compareOut = root.querySelector('[data-compare-out]');
    const variantInputs = [...form.querySelectorAll('input[name="variant_id"]')];
    const addonInputs = [...form.querySelectorAll('input[name="addons[]"]')];
    const qtyInput = form.querySelector('input[name="quantity"]');
    const stockOut = root.querySelector('[data-stock-out]');

    const fmt = (n) =>
        (Number.isInteger(n) ? n.toLocaleString('tr-TR') : n.toLocaleString('tr-TR', { minimumFractionDigits: 2 })) +
        ' TL';

    const recalc = () => {
        const variant = variantInputs.find((i) => i.checked);
        const base = parseFloat(variant?.dataset.price ?? form.dataset.basePrice ?? '0');
        const compare = parseFloat(variant?.dataset.compare || form.dataset.baseCompare || '0');
        const addons = addonInputs.filter((i) => i.checked).reduce((s, i) => s + parseFloat(i.dataset.price || '0'), 0);
        const qty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

        if (priceOut) priceOut.textContent = fmt((base + addons) * qty);

        if (compareOut) {
            const has = compare > base;
            compareOut.textContent = has ? fmt((compare + addons) * qty) : '';
            compareOut.hidden = !has;
        }

        if (stockOut && variant) {
            stockOut.textContent = variant.dataset.stockLabel || '';
            stockOut.dataset.state = variant.dataset.stockState || 'in_stock';
        }
    };

    [...variantInputs, ...addonInputs].forEach((i) => i.addEventListener('change', recalc));
    qtyInput?.addEventListener('input', recalc);

    form.querySelectorAll('[data-qty-step]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const step = parseInt(btn.dataset.qtyStep, 10);
            qtyInput.value = Math.max(1, Math.min(99, parseInt(qtyInput.value || '1', 10) + step));
            recalc();
        });
    });

    recalc();
}

/* -------------------------------------------------------------------------
 * WhatsApp'tan stok sor — tıklamayı kaydet, hazır mesajla aç
 * ---------------------------------------------------------------------- */
function initStockInquiry(root = document) {
    root.querySelectorAll('[data-stock-ask]').forEach((btn) => {
        if (btn.dataset.askBound) return;
        btn.dataset.askBound = '1';

        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            // Ürün sayfasında/pencerede seçili boyu da ilet
            const scope = btn.closest('[data-quick-body]') || document;
            const variantName =
                scope.querySelector('input[name="variant_id"]:checked')?.dataset.name ||
                btn.dataset.variantName ||
                '';

            btn.classList.add('is-busy');

            try {
                const res = await fetch(btn.dataset.stockAsk, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: btn.dataset.productId || null,
                        variant_name: variantName,
                        source: btn.dataset.source || 'product',
                    }),
                });

                const data = await res.json();
                window.open(data.url, '_blank', 'noopener');
            } catch {
                // Ağ hatasında bile müşteriyi WhatsApp'a gönder
                window.open(btn.dataset.fallback, '_blank', 'noopener');
            } finally {
                btn.classList.remove('is-busy');
            }
        });
    });
}

/* -------------------------------------------------------------------------
 * Kart notu — hazır cümle çipleri (kasa, 3. adım)
 *
 * Eskiden canlı önizleme kartı da vardı ve önizleme yoksa fonksiyon erken
 * çıkıyordu; kasada önizleme hiç olmadığı için çipler orada çalışmıyordu.
 * ---------------------------------------------------------------------- */
function initCardSuggestions() {
    const input = document.querySelector('[data-card-input]');
    if (!input) return;

    document.querySelectorAll('[data-card-suggestion]').forEach((chip) => {
        chip.addEventListener('click', () => {
            input.value = chip.dataset.cardSuggestion;
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    });
}

/* -------------------------------------------------------------------------
 * Hero fotoğraf geçişi — logonun mozaik ızgarasıyla
 *
 * Karolar GİDEN fotoğrafın dilimlerini gösterir; çaprazlama kapanınca
 * altındaki yeni fotoğraf açılır. Fotoğraflar arasında opaklık geçişi
 * yapılmaz: hero'nun transform'u kaydırmaya bağlı (--p) ve animasyonla
 * çakışırdı.
 * ---------------------------------------------------------------------- */
function initHeroSlides() {
    const frame = document.querySelector('.hero__frame');
    const tiles = frame?.querySelector('[data-hero-tiles]');
    if (!frame || !tiles) return;

    const slides = [...frame.querySelectorAll('.hero__img')];
    if (slides.length < 2) return;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let index = 0;

    setInterval(() => {
        // Sekme arka plandayken boşuna çalışmasın
        if (document.hidden) return;

        const current = slides[index];
        index = (index + 1) % slides.length;
        const next = slides[index];

        tiles.style.setProperty('--bg', `url("${current.currentSrc || current.src}")`);

        next.classList.add('is-on');
        current.classList.remove('is-on');

        // Animasyonu baştan başlatmak için sınıfı sıfırla; araya bir
        // yeniden akış (reflow) girmezse tarayıcı değişikliği görmez.
        tiles.classList.remove('is-wiping');
        void tiles.offsetWidth;
        tiles.classList.add('is-wiping');
    }, 7000);
}

/* -------------------------------------------------------------------------
 * Hareket azaltma tercihi: kendiliğinden oynayan video başlatılmaz.
 * İşletim sisteminde "hareketi azalt" açıksa dönen görüntü rahatsız eder;
 * ziyaretçi isterse denetimlerden kendisi oynatır.
 * ---------------------------------------------------------------------- */
function initAutoplayGuard() {
    if (!matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.querySelectorAll('video[autoplay]').forEach((video) => {
        video.autoplay = false;
        video.removeAttribute('autoplay');
        video.pause();
    });
}

/* -------------------------------------------------------------------------
 * Aynı gün gönderim geri sayımı (KKTC saati)
 * Hem üstteki şeridi hem her ürün kartındaki satırı besler.
 * Sunucu ilk değeri basar; burası yalnızca tazeler — JS kapalıysa da doğru.
 * ---------------------------------------------------------------------- */
function initCountdowns() {
    const strip = document.querySelector('[data-cutoff]');
    const cutoffMinutes = parseInt(
        strip?.dataset.cutoff || document.querySelector('meta[name="ship-cutoff"]')?.content || '900',
        10
    );

    const label = (mins) => {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return h > 0 ? `${h} saat ${m} dakika` : `${m} dakika`;
    };

    const tick = () => {
        const parts = new Intl.DateTimeFormat('tr-TR', {
            timeZone: 'Asia/Famagusta',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).formatToParts(new Date());

        const h = parseInt(parts.find((p) => p.type === 'hour').value, 10);
        const m = parseInt(parts.find((p) => p.type === 'minute').value, 10);
        const remaining = cutoffMinutes - (h * 60 + m);
        const open = remaining > 0;

        if (strip) {
            strip.dataset.state = open ? (remaining < 60 ? 'urgent' : 'open') : 'closed';
            const out = strip.querySelector('[data-cutoff-out]');
            if (out && open) out.textContent = label(remaining);
        }

        document.querySelectorAll('[data-ship]').forEach((el) => {
            el.dataset.shipOpen = open ? '1' : '0';
            const out = el.querySelector('[data-ship-out]');
            if (out && open) out.textContent = label(remaining);
        });
    };

    tick();
    setInterval(tick, 30000);
}

/* -------------------------------------------------------------------------
 * Favoriler — hesap gerekmez, tarayıcıda saklanır
 * ---------------------------------------------------------------------- */
const FAV_KEY = 'ayparcasi.favoriler';

function readFavs() {
    try {
        const raw = JSON.parse(localStorage.getItem(FAV_KEY));
        return Array.isArray(raw) ? raw.map(String) : [];
    } catch {
        return [];
    }
}

function writeFavs(list) {
    try {
        localStorage.setItem(FAV_KEY, JSON.stringify(list));
    } catch {
        /* özel sekmede yazılamayabilir — sessizce geç */
    }
}

const isAuthed = () => document.body.dataset.auth === '1';

function paintFav(id, on) {
    document.querySelectorAll(`[data-fav="${id}"]`).forEach((btn) => {
        btn.setAttribute('aria-pressed', String(on));
        btn.setAttribute('aria-label', on ? 'Favorilerden çıkar' : 'Favorilere ekle');
        btn.setAttribute('title', on ? 'Favorilerden çıkar' : 'Favorilere ekle');
    });
}

function initFavs(root = document) {
    const authed = isAuthed();

    // Misafirde işaretleri tarayıcıdan boya; girişliyse sunucu zaten bastı
    if (!authed) {
        const favs = readFavs();
        root.querySelectorAll('[data-fav]').forEach((btn) => paintFav(btn.dataset.fav, favs.includes(String(btn.dataset.fav))));
    }

    root.querySelectorAll('[data-fav]').forEach((btn) => {
        if (btn.dataset.favBound) return;
        btn.dataset.favBound = '1';

        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            const id = String(btn.dataset.fav);
            const willBeOn = btn.getAttribute('aria-pressed') !== 'true';

            // Girişliyse sunucu kalıcı kaydeder
            if (isAuthed()) {
                paintFav(id, willBeOn); // iyimser boyama
                btn.classList.add('is-busy');

                try {
                    const res = await fetch(`${document.body.dataset.favUrl}/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            Accept: 'application/json',
                        },
                    });

                    if (!res.ok) throw new Error(res.status);

                    const data = await res.json();
                    paintFav(id, data.favorited);
                    toast(data.favorited ? `${btn.dataset.favName} favorilere eklendi.` : 'Favorilerden çıkarıldı.');
                } catch {
                    paintFav(id, !willBeOn); // geri al
                    toast('Kaydedemedik, bağlantınızı kontrol edin.');
                } finally {
                    btn.classList.remove('is-busy');
                }

                return;
            }

            // Misafir: tarayıcıda tut, giriş yapınca hesaba taşınır
            const list = readFavs();
            const at = list.indexOf(id);

            if (willBeOn) list.push(id);
            else if (at !== -1) list.splice(at, 1);

            writeFavs(list);
            paintFav(id, willBeOn);
            toast(willBeOn ? `${btn.dataset.favName} favorilere eklendi.` : 'Favorilerden çıkarıldı.');
        });
    });
}

/**
 * Giriş yapıldığında tarayıcıda biriken favorileri hesaba taşı.
 * Tekrar çalışsa da sonuç değişmez; taşındıktan sonra yerel liste silinir.
 */
async function syncFavs() {
    if (!isAuthed()) return;

    const local = readFavs();
    if (!local.length) return;

    try {
        const res = await fetch(document.body.dataset.favMerge, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ ids: local.map(Number) }),
        });

        if (!res.ok) return;

        const data = await res.json();
        writeFavs([]);
        data.ids.forEach((id) => paintFav(String(id), true));
    } catch {
        /* bir dahaki açılışta tekrar denenir */
    }
}

/* -------------------------------------------------------------------------
 * Hızlı bakış — ürün sayfasına gitmeden sepete ekle
 * ---------------------------------------------------------------------- */
function initQuickView() {
    const dialog = document.querySelector('.quick-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const body = dialog.querySelector('[data-quick-body]');
    const loading = '<div class="quick-dialog__loading"><span class="spinner"></span></div>';

    const open = async (url) => {
        body.innerHTML = loading;
        dialog.showModal();
        document.body.classList.add('is-locked');

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
            if (!res.ok) throw new Error(res.status);

            body.innerHTML = await res.text();

            // Pencere içeriği yeni geldi — etkileşimleri bağla
            initProduct(body);
            initStockInquiry(body);
            initFavs(body);
        } catch {
            // Açılamazsa müşteriyi ürün sayfasına gönder
            dialog.close();
            window.location.href = url.replace(/\/hizli-bakis$/, '');
        }
    };

    document.querySelectorAll('[data-quickview]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            open(btn.dataset.quickview);
        });
    });

    const close = () => dialog.close();
    dialog.querySelector('[data-quick-close]')?.addEventListener('click', close);

    // Dış alana tıklayınca kapan
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) close();
    });

    dialog.addEventListener('close', () => {
        document.body.classList.remove('is-locked');
        body.innerHTML = loading;
    });
}

/* -------------------------------------------------------------------------
 * Anlık bildirim (JS ile oluşturulanlar için)
 * ---------------------------------------------------------------------- */
function toast(message) {
    let host = document.querySelector('.toasts');

    if (!host) {
        host = document.createElement('div');
        host.className = 'toasts';
        host.setAttribute('role', 'status');
        host.setAttribute('aria-live', 'polite');
        document.body.appendChild(host);
    }

    const el = document.createElement('div');
    el.className = 'toast';
    el.textContent = message;
    host.appendChild(el);

    setTimeout(() => {
        el.classList.add('is-out');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
    }, 3200);
}

/* -------------------------------------------------------------------------
 * Bildirim balonu
 * ---------------------------------------------------------------------- */
function initToasts() {
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const close = () => {
            toast.classList.add('is-out');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        };

        toast.querySelector('[data-toast-close]')?.addEventListener('click', close);
        setTimeout(close, 6000);
    });
}

/* -------------------------------------------------------------------------
 * Sepet adet formu — değişince kendiliğinden gönder
 * ---------------------------------------------------------------------- */
function initCartForms() {
    document.querySelectorAll('[data-auto-submit]').forEach((input) => {
        let timer;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => input.form.requestSubmit(), 500);
        });
    });
}

/* -------------------------------------------------------------------------
 * Kasa — bölge seçimi teslimat ücretini canlı günceller
 * ---------------------------------------------------------------------- */
function initCheckout() {
    const form = document.querySelector('[data-checkout]');
    if (!form) return;

    const zoneInputs = [...form.querySelectorAll('input[name="delivery_zone_id"]')];
    const feeOut = document.querySelector('[data-fee-out]');
    const totalOut = document.querySelector('[data-total-out]');
    const dateInput = form.querySelector('input[name="delivery_date"]');
    const sameDayNote = document.querySelector('[data-sameday-note]');
    const freeNote = document.querySelector('[data-free-note]');

    const subtotal = parseFloat(form.dataset.subtotal || '0');
    const discount = parseFloat(form.dataset.discount || '0');
    const freeDelivery = form.dataset.freeDelivery === '1';

    const fmt = (n) =>
        (Number.isInteger(n) ? n.toLocaleString('tr-TR') : n.toLocaleString('tr-TR', { minimumFractionDigits: 2 })) +
        ' TL';

    const recalc = () => {
        const zone = zoneInputs.find((i) => i.checked);
        let fee = 0;

        if (zone && !freeDelivery) {
            const base = parseFloat(zone.dataset.fee || '0');
            const freeOver = zone.dataset.freeOver ? parseFloat(zone.dataset.freeOver) : null;
            fee = freeOver !== null && subtotal >= freeOver ? 0 : base;
        }

        if (feeOut) feeOut.textContent = fee === 0 ? 'Ücretsiz' : fmt(fee);
        if (totalOut) totalOut.textContent = fmt(Math.max(0, subtotal - discount) + fee);

        // Ücretsiz teslimat eşiği — eşiğe ne kadar kaldığını söyler.
        // Eşik ARA TOPLAM üzerinden karşılaştırılır (kupon indirimi düşülmeden),
        // sunucudaki DeliveryZone::feeFor ile birebir aynı kural.
        if (freeNote) {
            const freeOver = zone && zone.dataset.freeOver ? parseFloat(zone.dataset.freeOver) : null;
            const base = zone ? parseFloat(zone.dataset.fee || '0') : 0;
            let text = '';

            if (freeDelivery) {
                text = 'Kuponunuz teslimatı ücretsiz yapıyor.';
            } else if (freeOver !== null && base > 0) {
                const kalan = freeOver - subtotal;
                text =
                    kalan > 0
                        ? fmt(kalan) + ' daha ekleyin, teslimat ücretsiz olsun.'
                        : 'Sepetiniz eşiği geçti — teslimat ücretsiz.';
            }

            freeNote.textContent = text;
            freeNote.hidden = text === '';
            freeNote.dataset.state = freeDelivery || (freeOver !== null && subtotal >= freeOver) ? 'ok' : 'info';
        }

        if (sameDayNote && zone) {
            const same = zone.dataset.sameDay === '1';
            sameDayNote.textContent = same
                ? zone.dataset.name + ' bölgesine bugün teslim edebiliriz.'
                : zone.dataset.name + ' bölgesine en erken yarın teslim edebiliriz.';
            sameDayNote.dataset.state = same ? 'ok' : 'warn';

            if (!same && dateInput && dateInput.value === dateInput.dataset.today) {
                dateInput.value = dateInput.dataset.tomorrow;
            }
        }
    };

    zoneInputs.forEach((i) => i.addEventListener('change', recalc));
    recalc();

    // Kayıtlı adresi seçince formu doldur
    document.querySelectorAll('[data-fill]').forEach((btn) => {
        btn.addEventListener('click', () => {
            let fill;
            try {
                fill = JSON.parse(btn.dataset.fill);
            } catch {
                return;
            }

            document.querySelectorAll('.saved-address').forEach((b) => b.classList.remove('is-on'));
            btn.classList.add('is-on');

            for (const [name, value] of Object.entries(fill)) {
                if (name === 'delivery_zone_id') {
                    const zone = form.querySelector(`input[name="delivery_zone_id"][value="${value}"]`);
                    if (zone) {
                        zone.checked = true;
                        zone.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    continue;
                }

                const field = form.querySelector(`[name="${name}"]`);
                if (field) field.value = value ?? '';
            }
        });
    });

    // "Bu adresi kaydet" işaretlenince başlık alanı açılsın
    const saveBox = form.querySelector('[data-save-address]');
    const titleField = document.querySelector('[data-address-title]');

    if (saveBox && titleField) {
        const sync = () => {
            titleField.hidden = !saveBox.checked;
        };
        saveBox.addEventListener('change', sync);
        sync();
    }

    form.querySelectorAll('input[name="payment_method"]').forEach((input) => {
        input.addEventListener('change', () => {
            form.querySelectorAll('[data-method-note]').forEach((note) => {
                note.hidden = note.dataset.methodNote !== input.value;
            });
        });
    });

    form.querySelector('input[name="payment_method"]:checked')?.dispatchEvent(new Event('change'));
}

/* -------------------------------------------------------------------------
 * Sipariş sonrası WhatsApp'ı aç
 * ---------------------------------------------------------------------- */
function initOrderWhatsapp() {
    const el = document.querySelector('[data-auto-whatsapp]');
    if (!el) return;

    setTimeout(() => window.open(el.dataset.autoWhatsapp, '_blank', 'noopener'), 900);
}

/* ---------------------------------------------------------------------- */

function boot() {
    initMotion();
    initHeader();
    initOverlays();
    initAccordions();
    initPanels();
    initGallery();
    initProduct();
    initStockInquiry();
    initFavs();
    syncFavs();
    initQuickView();
    initCardSuggestions();
    initHeroSlides();
    initAutoplayGuard();
    initCountdowns();
    initToasts();
    initCartForms();
    initCheckout();
    initOrderWhatsapp();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
