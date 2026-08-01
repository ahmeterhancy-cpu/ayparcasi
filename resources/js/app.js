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
        document.querySelectorAll('.overlay.is-open').forEach((p) => p.classList.remove('is-open'));
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
 * Spotlight — yapışkan görsel değişirken yanındaki adım da öne çıksın
 * ---------------------------------------------------------------------- */
function initSpot() {
    document.querySelectorAll('.spot[data-swap]').forEach((section) => {
        const steps = [...section.querySelectorAll('.spot__step')];
        if (!steps.length) return;

        section.addEventListener('swap', (e) => {
            steps.forEach((s, k) => s.classList.toggle('is-active', k === e.detail.index));
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
function initProduct() {
    const gallery = document.querySelector('[data-gallery]');

    if (gallery) {
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

    const form = document.querySelector('[data-product-form]');
    if (!form) return;

    // Fiyat ve stok göstergeleri formun dışında (başlık bloğunda) duruyor
    const priceOut = document.querySelector('[data-price-out]');
    const compareOut = document.querySelector('[data-compare-out]');
    const variantInputs = [...form.querySelectorAll('input[name="variant_id"]')];
    const addonInputs = [...form.querySelectorAll('input[name="addons[]"]')];
    const qtyInput = form.querySelector('input[name="quantity"]');
    const stockOut = document.querySelector('[data-stock-out]');

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
function initStockInquiry() {
    document.querySelectorAll('[data-stock-ask]').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const variantName =
                document.querySelector('input[name="variant_id"]:checked')?.dataset.name ||
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
 * Kart notu — canlı önizleme
 * ---------------------------------------------------------------------- */
function initCardPreview() {
    const input = document.querySelector('[data-card-input]');
    const out = document.querySelector('[data-card-out]');
    if (!input || !out) return;

    const placeholder = out.dataset.placeholder || '';

    const sync = () => {
        out.textContent = input.value.trim() || placeholder;
        out.classList.toggle('is-empty', !input.value.trim());
    };

    input.addEventListener('input', sync);
    sync();

    document.querySelectorAll('[data-card-suggestion]').forEach((chip) => {
        chip.addEventListener('click', () => {
            input.value = chip.dataset.cardSuggestion;
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    });
}

/* -------------------------------------------------------------------------
 * Aynı gün teslimat geri sayımı (KKTC saati)
 * ---------------------------------------------------------------------- */
function initCutoff() {
    const el = document.querySelector('[data-cutoff]');
    if (!el) return;

    const out = el.querySelector('[data-cutoff-out]');
    const cutoffMinutes = parseInt(el.dataset.cutoff, 10); // gün içi dakika (15:00 → 900)

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

        if (remaining > 0 && out) {
            const hrs = Math.floor(remaining / 60);
            const mins = remaining % 60;
            out.textContent = hrs > 0 ? `${hrs} saat ${mins} dakika` : `${mins} dakika`;
            el.dataset.state = remaining < 60 ? 'urgent' : 'open';
        } else {
            el.dataset.state = 'closed';
        }
    };

    tick();
    setInterval(tick, 30000);
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
    initSpot();
    initProduct();
    initStockInquiry();
    initCardPreview();
    initCutoff();
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
