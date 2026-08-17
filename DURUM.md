# Ay Parçası — Proje Durumu

> Bu dosya, yeni bir oturuma devretmek için yazıldı.
> Son güncelleme: 2026-08-12

**CANLIDA: https://ayparcasicicekci.com**
Laravel 13 + Filament 5 · 82 commit · 134/134 test · depo **public**:
`github.com/ahmeterhancy-cpu/ayparcasi` (dal `main`)

## Çalıştırma

```bash
cd "F:\Yazılımlar\ayparcasi"
npm run start          # php artisan serve :8126 + vite, birlikte
```

| | |
|---|---|
| Vitrin | http://127.0.0.1:8126 |
| Panel | http://127.0.0.1:8126/admin |
| Panel girişi | `admin@ayparcasicicekci.com` — parola için aşağıya bakın |
| Demo müşteri | `musteri@ornek.com` — parola için aşağıya bakın |
| Testler | `php artisan test` |
| Biçimlendirme | `./vendor/bin/pint` |

> `localhost` bu ortamda engelli, **`127.0.0.1`** kullanın.
> **Parolalar depoda yazmaz** (depo herkese açık). `db:seed` çalıştırınca
> rastgele üretilip ekrana basılır; kendi parolanızı vermek isterseniz
> `.env` içine `SEED_ADMIN_PASSWORD` ve `SEED_CUSTOMER_PASSWORD` yazın.
> Canlıdaki yönetici hesabı `php artisan admin:olustur` ile açılır.

---

## Canlı sunucu — bilinmesi şart olanlar

Turhost/cPanel · kullanıcı `aypa8479` · PHP 8.4 (PHP Selector).
Tam anlatım **DEPLOY.md**'de. Kısıtlar:

- **Alan adının kök dizini değiştirilemiyor** → uygulama
  `public_html/ayparcasi_app/` altında, Laravel'in `public/` içeriği doğrudan
  `public_html`'de. `ayparcasi_app/.htaccess` klasörü web'e kapatır;
  **o dosya olmazsa `.env` indirilebilir** (kurulumdan sonra 403 doğrulandı).
- **SSH/Terminal YOK** → tek seferlik `artisan` işleri `.cpanel.yml` görevlerinde.
- **Sunucuda composer YOK** → `vendor/` depoda (`git add -f`). Bağımlılık
  değişince DEPLOY.md 2-E'deki sırayı izleyin.
- **Sembolik bağ takip edilmiyor** → görseller `public_html/storage` içinde
  gerçek klasörde; `.env`'deki `FILESYSTEM_PUBLIC_ROOT` oraya yönlendiriyor.
- Yayın: cPanel → Git Version Control → **Update from Remote**, sonra
  **Deploy HEAD Commit**. İkisi ayrı iş; yalnız ikincisine basmak eski kodu
  yeniden kurar. **Last Deployed SHA**'nın değiştiğini doğrulayın.

### Canlıda zaman kaybettiren beş sessiz tuzak

Hiçbiri açık hata vermedi; hepsi DEPLOY.md sorun tablosunda:

1. **`.cpanel.yml` geçersiz YAML** — görev metninde `iki nokta + boşluk`
   varsa cPanel dosyayı reddedip deploy'u düşürür ama **"Last Deployed" son
   BAŞARILI commit'te kalır**. Saatlerce "kuyruk takıldı" sanıldı.
2. **Config önbelleği `env()`'i öldürür** — `optimize` sonrası Laravel `.env`'i
   yüklemez; config dosyaları DIŞINDAKİ `env()` null döner → `config/shop.php`.
3. **`DB_HOST=127.0.0.1`** — cPanel yetkiyi `@localhost` verir; TCP bağlantısı
   başka kimlik sayılıp reddedilir. `localhost` yazılacak.
4. **`vendor` PHP sürümü** — yerelde PHP 8.5 ile kurulunca "8.4.1 gerekir"
   kontrolü gömülüyordu. `composer.json` → `config.platform.php = 8.3.33`.
5. **Filament, Livewire'ı rastgele önekle servis eder**
   (`/livewire-172643c6/update`). Bakım perdesinin geçiş listesinde `livewire*`
   olmalı; `livewire/*` eşleşmez. Belirti: perde açıkken panele giriş
   yapılamıyor ve **yanlış parolada bile hata çıkmıyor** (istek hiç gitmiyor).

---

## Son oturumda eklenenler (2026-08-12)

**Yapım aşamasında perdesi** — Ayarlar → Site ayarları → "Yapım aşamasında".
Perdeyi yalnız **panel hesabıyla giriş** geçer. Panel, süren ödemeler ve mevcut
sipariş sayfaları hep açık. Yanıt önbelleğe alınmaz (LiteSpeed dahil).
Acil çıkış: phpMyAdmin → `settings` → `maintenance_enabled` = `0`.

**Ek ürünler ürün bazında** (`addon_product`). Sepete eklerken gelen kimlikler
ürünün kendi ekleriyle kesiştiriliyor — istek elle düzenlenip başka ürünün eki
sokulamıyor.

**Ürün ekleme yolları:**
- **Ürün şablonları** (Katalog → Ürün şablonları): metin, kategori, ek ürün,
  boy seti. Ürüne bağlı DEĞİL — şablonu sonradan düzeltmek eski ürünlere
  dokunmaz. Ürün ekranındaki **"Şablon çıkar"** ile mevcut üründen türetilir.
- **Katalogdan otomatik şablon**: `php artisan urun:sablon-olustur --haric=6`
  ya da şablon listesinde "Katalogdan oluştur". Ürün tipi ile özel gün
  kategorisini ayıracak yapısal sinyal YOK; hangi dalın atlanacağı sorulur.
- **Toplu fotoğraf** (Ürünler → Toplu fotoğraf): 30 fotoğrafa kadar, her biri
  için **yayından kaldırılmış** taslak. Ad dosya adından; `IMG_1234` atlanır.
- **Satır içi düzenleme**: ad, fiyat, "Satışta" hücrede. Taslağın adı değişince
  slug düzelir; **yayına girmiş üründe slug sabit kalır**.
- **Tezgâh modu** (`/admin/tezgah`): telefon için tek ekran.

**Ana sayfa**: teslimat bölümündeki bölge fiyat kartları kaldırıldı.
**Ürün listesi**: kategori rozetleri 2'ye indi (kalanı "ve N daha"),
"Öne çıkan" ve "Rozet" varsayılan gizli.

---

## Açık işler

1. 🔴 **Geçici panel parolası değiştirilmeli** — kurulum sırasında SQL ile elle
   eklendi ve sohbette açıkta geçti.
2. 🔴 **Teslimat ücretleri placeholder** — 7 bölge de 100 TL / 2.000 TL
   (Girne 150/2.500). Müşteri kasada bu rakamları görüyor.
3. 🔴 **Tiko kimlik bilgileri** + evrakla alan adı doğrulaması. Gelene kadar
   `TIKO_ENABLED=false`; havale ve kapıda ödeme çalışıyor.
4. 🟡 **Gerçek e-posta kutusu** (`merhaba@ayparcasicicekci.com`), çalışma
   saatleri, adresin kapı numarası.
5. 🟡 **Gerçek ürün fotoğrafları** — şu an demo görseller.
6. 🟡 **Canlıdaki kategori atamaları** — bazı ürünler 10'dan fazla kategoride
   (yerelde ortalama 1.7). Mağaza filtrelerini anlamsızlaştırıyor; sebebi
   doğrulanmadı, kullanıcıya soruldu.

---

## Tuzaklar (yerel geliştirme)

- **`DemoSeeder` panelden yapılan düzenlemeleri EZER** — `db:seed` çalıştırmayın.
- `vendor`'ın 6 oynak dosyası **skip-worktree** ile işaretli. Yeni makinede
  aynı işaretlemeyi yapın (DEPLOY.md 2-E), yoksa `git status` sürekli kirli
  görünür ve dosyaları geri almak yerel kurulumu bozar.
- Filament özel sayfalarda **Tailwind sınıfı çalışmaz**; panel marka katmanı
  düz CSS (`resources/views/filament/theme.blade.php`).
- **`Color::hex()` kullanmayın** — markanın rengini bozuyor.
- `@json(...)` içine parantezli/çok satırlı ifade yazmayın; `@php`'de hazırlayın.
- `php artisan serve` tek iş parçacıklı — eşzamanlı istek reddedilebilir.
- `storage/app/public` git'te yok; görseller ayrıca taşınır.
- `overflow: hidden` yerine `overflow: clip` (hidden tüm `position:sticky`'yi bozar).
