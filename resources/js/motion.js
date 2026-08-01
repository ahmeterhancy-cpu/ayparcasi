/**
 * Ay Parçası — hareket motoru
 * ---------------------------------------------------------------------------
 * Sıfır bağımlılık. Tek bir requestAnimationFrame döngüsü, kare başına tek
 * layout okuması. Tüm yazma işlemleri transform / opacity / CSS custom property
 * üzerinden yapılır; hiçbir sürücü layout tetiklemez.
 *
 * Kullanım (HTML tarafında):
 *   data-reveal="up|fade|mask|scale"   görünür olunca içeri gel
 *   data-stagger="60"                  çocukları sırayla getir (ms)
 *   data-scrub                         elemanın ilerlemesini --p (0→1) olarak yaz
 *   data-scrub-range="enter,cover"     ilerlemenin ölçüleceği aralık
 *   data-parallax="-0.15"              ilerlemeye bağlı dikey kayma
 *   data-split="words|chars"           metni saran span'lara böl
 *   data-magnetic="0.35"               imleci takip eden buton
 *   data-tilt="6"                      imlece göre 3B eğim
 *   data-count-to="1200"               görününce sayar
 *   data-draw                          SVG path'i çizer (scrub'a bağlı)
 *   data-swap                          scrub ilerlemesine göre çocuk değiştirir
 *
 * prefers-reduced-motion: reduce → tüm sürücüler kapanır, içerik anında görünür.
 */

const root = document.documentElement;
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

/* -------------------------------------------------------------------------
 * 1. Zamanlayıcı — tek rAF, tek ölçüm
 * ---------------------------------------------------------------------- */

class Scheduler {
    constructor() {
        this.drivers = [];
        this.running = false;
        this.frame = { y: 0, dy: 0, vh: 0, vw: 0, t: 0, ticking: false };
        this.needsMeasure = true;

        this._onScroll = () => this.request();
        this._onResize = () => {
            this.needsMeasure = true;
            this.request();
        };

        addEventListener('scroll', this._onScroll, { passive: true });
        addEventListener('resize', this._onResize, { passive: true });
        addEventListener('orientationchange', this._onResize, { passive: true });
    }

    add(driver) {
        this.drivers.push(driver);
        this.needsMeasure = true;
        this.request();
        return driver;
    }

    remeasure() {
        this.needsMeasure = true;
        this.request();
    }

    request() {
        if (this.running) return;
        this.running = true;
        requestAnimationFrame((t) => this.tick(t));
    }

    tick(t) {
        this.running = false;

        const f = this.frame;
        const y = window.scrollY || window.pageYOffset;
        f.dy = y - f.y;
        f.y = y;
        f.vh = window.innerHeight;
        f.vw = window.innerWidth;
        f.t = t;

        root.style.setProperty('--vh', f.vh + 'px');

        // Ölçüm (okuma) fazı — tüm getBoundingClientRect çağrıları burada
        if (this.needsMeasure) {
            for (const d of this.drivers) d.measure && d.measure(f);
            this.needsMeasure = false;
        }

        // Uygulama (yazma) fazı
        let live = false;
        for (const d of this.drivers) {
            if (d.update && d.update(f)) live = true;
        }

        // Devam eden yumuşatma varsa bir kare daha iste
        if (live) this.request();
    }
}

const scheduler = new Scheduler();

/* -------------------------------------------------------------------------
 * 2. Yardımcılar
 * ---------------------------------------------------------------------- */

const clamp = (v, a = 0, b = 1) => (v < a ? a : v > b ? b : v);
const lerp = (a, b, t) => a + (b - a) * t;

/** Yumuşak giriş/çıkış — motorun varsayılan eğrisi. */
const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

/** Değeri 3 basamağa yuvarla — gereksiz style yazımını engeller. */
const q = (v) => Math.round(v * 1000) / 1000;

function absoluteTop(el) {
    let top = 0;
    let node = el;
    while (node) {
        top += node.offsetTop;
        node = node.offsetParent;
    }
    return top;
}

/* -------------------------------------------------------------------------
 * 3. Scrub — elemanın viewport içindeki ilerlemesini --p olarak yazar
 * ---------------------------------------------------------------------- */

/**
 * Aralık modları:
 *   'enter'  → eleman alt kenardan girerken 0, üst kenardan çıkarken 1
 *   'in'     → eleman tamamen göründüğünde 1 (sayfa sonundaki öğeler için;
 *              'enter' orada asla 1'e ulaşamaz çünkü eleman ekranın
 *              üstünden hiç çıkmaz)
 *   'cover'  → eleman ekranı kapladığı sürece 0→1 (sticky bölümler için)
 *   'self'   → eleman viewport'un ortasından geçerken 0→1
 */
class Scrub {
    constructor(el) {
        this.el = el;
        this.mode = el.dataset.scrubRange || 'enter';
        this.smooth = parseFloat(el.dataset.scrubSmooth || '0.12');
        this.current = null;
        this.target = 0;
        this.top = 0;
        this.height = 0;

        // Parallax alt-sürücüleri: kendi ve içindeki [data-parallax]
        this.movers = [...el.querySelectorAll('[data-parallax]')];
        if (el.hasAttribute('data-parallax')) this.movers.unshift(el);
    }

    measure() {
        this.top = absoluteTop(this.el);
        this.height = this.el.offsetHeight;
    }

    progress(f) {
        const start = this.top;
        const end = this.top + this.height;

        if (this.mode === 'cover') {
            // Sticky bölüm: bölümün kaydırılabilir yüksekliği boyunca 0→1
            const span = Math.max(1, this.height - f.vh);
            return clamp((f.y - start) / span);
        }

        if (this.mode === 'in') {
            // Üst kenar ekrana girerken 0, alt kenar ekranın altına
            // hizalandığında 1 — yani eleman tamamen göründüğünde dolar.
            return clamp((f.y + f.vh - start) / Math.max(1, this.height));
        }

        if (this.mode === 'self') {
            const center = start + this.height / 2;
            return clamp((f.y + f.vh / 2 - center) / f.vh + 0.5);
        }

        // 'enter': alt kenardan girip üst kenardan çıkana kadar
        const span = this.height + f.vh;
        return clamp((f.y + f.vh - start) / span);
    }

    update(f) {
        this.target = this.progress(f);

        if (this.current === null || reduced.matches) {
            this.current = this.target;
        } else {
            this.current = lerp(this.current, this.target, this.smooth);
        }

        const diff = Math.abs(this.target - this.current);
        if (diff < 0.0005) this.current = this.target;

        const p = q(this.current);
        this.el.style.setProperty('--p', p);

        for (const m of this.movers) {
            const rate = parseFloat(m.dataset.parallax || '0');
            const unit = m.dataset.parallaxUnit || 'vh';
            const amount = (p - 0.5) * rate * 100;
            m.style.setProperty('--shift', q(amount) + (unit === 'vh' ? 'vh' : '%'));
        }

        return diff > 0.0005;
    }
}

/* -------------------------------------------------------------------------
 * 4. Reveal — görünür olunca .is-in
 * ---------------------------------------------------------------------- */

function initReveal() {
    const nodes = [...document.querySelectorAll('[data-reveal], [data-stagger]')];
    if (!nodes.length) return;

    if (reduced.matches) {
        nodes.forEach((n) => {
            n.classList.add('is-in');
            n.querySelectorAll(':scope > *').forEach((c) => c.classList.add('is-in'));
        });
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                const el = entry.target;

                if (el.hasAttribute('data-stagger')) {
                    const step = parseInt(el.dataset.stagger || '80', 10);
                    [...el.children].forEach((child, i) => {
                        child.style.setProperty('--delay', i * step + 'ms');
                        child.classList.add('is-in');
                    });
                }

                el.classList.add('is-in');
                io.unobserve(el);
            }
        },
        { rootMargin: '0px 0px -12% 0px', threshold: 0.01 }
    );

    nodes.forEach((n) => io.observe(n));
}

/* -------------------------------------------------------------------------
 * 5. Split — kelime / harf sarmalayıcı
 * ---------------------------------------------------------------------- */

function splitNode(el) {
    const mode = el.dataset.split || 'words';
    const text = el.textContent.replace(/\s+/g, ' ').trim();
    if (!text) return;

    const frag = document.createDocumentFragment();
    let index = 0;

    const makePiece = (content, isSpace) => {
        if (isSpace) {
            frag.appendChild(document.createTextNode(' '));
            return;
        }
        const outer = document.createElement('span');
        outer.className = 'sp';
        const inner = document.createElement('span');
        inner.className = 'sp-i';
        inner.style.setProperty('--i', index++);
        inner.textContent = content;
        outer.appendChild(inner);
        frag.appendChild(outer);
    };

    if (mode === 'chars') {
        for (const ch of text) makePiece(ch, ch === ' ');
    } else {
        const words = text.split(' ');
        words.forEach((w, i) => {
            makePiece(w, false);
            if (i < words.length - 1) makePiece(null, true);
        });
    }

    el.textContent = '';
    el.appendChild(frag);
    el.classList.add('is-split');
    el.style.setProperty('--n', index);
}

function initSplit() {
    document.querySelectorAll('[data-split]').forEach(splitNode);
}

/* -------------------------------------------------------------------------
 * 6. Magnetic — imleci takip eden düğme
 * ---------------------------------------------------------------------- */

class Magnetic {
    constructor(el) {
        this.el = el;
        this.strength = parseFloat(el.dataset.magnetic || '0.3');
        this.x = 0;
        this.y = 0;
        this.tx = 0;
        this.ty = 0;
        this.active = false;

        el.addEventListener('pointerenter', () => (this.active = true));
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            this.tx = (e.clientX - (r.left + r.width / 2)) * this.strength;
            this.ty = (e.clientY - (r.top + r.height / 2)) * this.strength;
            scheduler.request();
        });
        el.addEventListener('pointerleave', () => {
            this.active = false;
            this.tx = 0;
            this.ty = 0;
            scheduler.request();
        });
    }

    update() {
        this.x = lerp(this.x, this.tx, 0.18);
        this.y = lerp(this.y, this.ty, 0.18);

        const done = Math.abs(this.x - this.tx) < 0.05 && Math.abs(this.y - this.ty) < 0.05;
        if (done) {
            this.x = this.tx;
            this.y = this.ty;
        }

        this.el.style.setProperty('--mx', q(this.x) + 'px');
        this.el.style.setProperty('--my', q(this.y) + 'px');

        return !done;
    }
}

/* -------------------------------------------------------------------------
 * 7. Tilt — imlece göre hafif 3B eğim
 * ---------------------------------------------------------------------- */

function initTilt() {
    if (reduced.matches) return;

    document.querySelectorAll('[data-tilt]').forEach((el) => {
        const max = parseFloat(el.dataset.tilt || '6');

        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width - 0.5;
            const py = (e.clientY - r.top) / r.height - 0.5;
            el.style.setProperty('--rx', q(-py * max) + 'deg');
            el.style.setProperty('--ry', q(px * max) + 'deg');
            el.style.setProperty('--gx', q((px + 0.5) * 100) + '%');
            el.style.setProperty('--gy', q((py + 0.5) * 100) + '%');
        });

        el.addEventListener('pointerleave', () => {
            el.style.setProperty('--rx', '0deg');
            el.style.setProperty('--ry', '0deg');
        });
    });
}

/* -------------------------------------------------------------------------
 * 8. Counter — görününce say
 * ---------------------------------------------------------------------- */

function initCounters() {
    const nodes = [...document.querySelectorAll('[data-count-to]')];
    if (!nodes.length) return;

    const format = (v, decimals) =>
        v.toLocaleString('tr-TR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

    const run = (el) => {
        const to = parseFloat(el.dataset.countTo);
        const decimals = parseInt(el.dataset.countDecimals || '0', 10);
        const duration = parseInt(el.dataset.countDuration || '1400', 10);

        if (reduced.matches) {
            el.textContent = format(to, decimals);
            return;
        }

        const start = performance.now();
        const step = (now) => {
            const t = clamp((now - start) / duration);
            el.textContent = format(to * easeOutExpo(t), decimals);
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const io = new IntersectionObserver(
        (entries) => {
            for (const e of entries) {
                if (!e.isIntersecting) continue;
                run(e.target);
                io.unobserve(e.target);
            }
        },
        { threshold: 0.4 }
    );

    nodes.forEach((n) => io.observe(n));
}

/* -------------------------------------------------------------------------
 * 9. Draw — SVG path'i scrub ilerlemesine göre çizer
 * ---------------------------------------------------------------------- */

function initDraw() {
    document.querySelectorAll('[data-draw]').forEach((path) => {
        const len = path.getTotalLength();
        // Yalnızca uzunluğu yaz — dashoffset'i CSS, --p üzerinden sürer.
        // Buraya inline dashoffset yazmak CSS kuralını ezerdi.
        path.style.setProperty('--len', len);
        path.style.strokeDasharray = len;
    });
}

/* -------------------------------------------------------------------------
 * 10. Swap — scrub ilerlemesine göre aktif çocuğu değiştirir
 * ---------------------------------------------------------------------- */

class Swap {
    constructor(el) {
        this.el = el;
        this.items = [...el.querySelectorAll('[data-swap-item]')];
        this.index = -1;
    }

    update() {
        const p = parseFloat(this.el.style.getPropertyValue('--p') || '0');
        const n = this.items.length;
        if (!n) return false;

        const i = Math.min(n - 1, Math.floor(p * n * 0.999));
        if (i === this.index) return false;

        this.index = i;
        this.items.forEach((item, k) => item.classList.toggle('is-active', k === i));
        this.el.style.setProperty('--swap-index', i);
        this.el.dispatchEvent(new CustomEvent('swap', { detail: { index: i } }));
        return false;
    }
}

/* -------------------------------------------------------------------------
 * 11. Kurulum
 * ---------------------------------------------------------------------- */

export function initMotion() {
    root.dataset.motion = reduced.matches ? 'off' : 'on';

    initSplit();
    initReveal();
    initTilt();
    initCounters();
    initDraw();

    document.querySelectorAll('[data-scrub]').forEach((el) => scheduler.add(new Scrub(el)));
    document.querySelectorAll('[data-swap]').forEach((el) => scheduler.add(new Swap(el)));

    if (!reduced.matches) {
        document.querySelectorAll('[data-magnetic]').forEach((el) => scheduler.add(new Magnetic(el)));
    }

    // Görseller yüklendikçe konumlar kayar — yeniden ölç
    document.querySelectorAll('img').forEach((img) => {
        if (img.complete) return;
        img.addEventListener('load', () => scheduler.remeasure(), { once: true });
    });

    // Yazı tipleri geç geldiğinde metin yüksekliği değişir
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => scheduler.remeasure());
    }

    root.classList.add('motion-ready');
    scheduler.remeasure();
}

export { scheduler, clamp, lerp, q };
