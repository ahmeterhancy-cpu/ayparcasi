# Ay Parçası — Proje Durumu

> Bu dosya, yeni bir oturuma devretmek için yazıldı.
> Son güncelleme: 2026-08-01

## Çalıştırma

```bash
cd "F:\Yazılımlar\ayparcasi"
npm run start          # php artisan serve :8126 + vite, birlikte
```

| | |
|---|---|
| Vitrin | http://localhost:8126 |
| Panel | http://localhost:8126/admin |
| Panel girişi | `admin@ayparcasicicekci.com` / `ayparcasi2026` |
| Demo müşteri | `musteri@ornek.com` / `musteri2026` |
| Testler | `./vendor/bin/phpunit` — **77/77, 284 doğrulama** |
| Biçimlendirme | `./vendor/bin/pint` |
| Sıfırlama | `php artisan migrate:fresh --seed` |

## Yığın

Laravel **13.23** + Filament **5.7** + SQLite (yerel) · TR tek dil · `Asia/Famagusta`

> Filament 4.x'in **tüm** sürümlerinde güvenlik uyarısı var, composer kurmuyor.
> Laravel 13'ün eşi zaten Filament 5.

Ön yüz: **CSS çatısı yok** (Tailwind kaldırıldı), **JS kütüphanesi yok**.
Animasyon motoru elle yazıldı: `resources/js/motion.js` (~15 kB).
Fontlar (Fraunces + Manrope) Laravel'in font eklentisiyle **self-host** —
çalışma anında hiçbir dış isteğe çıkılmıyor.

## Tasarım dili

Logodan türetildi: mozaik üçgen motifi, kemer (`--arch`) formu.
Palet: petrol `#0e2c34` · ay sarısı `#f4b02a` · turkuaz `#4cbfc4` · mercan `#db4a32` · kağıt `#fcf8f2`

Ana sayfada her kaydırma farklı bir olay: mozaik perde açılışı → çerçeveye
çekilen hero → canlı aynı-gün sayacı → genişleyen özel gün panelleri →
yapışkan spotlight → dönen mozaik halka → çizilen teslimat rotası →
canlı kart notu → parallax galeri → değişen yorumlar → SSS → günlük.

## Yapılanlar

**Katalog** — kategori (iç içe), 40 ürün, boy varyantları (ayrı fiyat+stok),
ek ürünler, indirim tarihi planlama, ürün CSV içe/dışa aktarma.

**Satış** — sepet (fiyatlar her okumada DB'den tazelenir), kasa (alıcı ≠ sipariş
veren, bölge, tarih, saat aralığı, kart notu, KVKK), kupon (kişi başı limit,
ürün/kategori kapsamı, indirimli üründe geçmeme, e-posta kısıtı).

**Ödeme** — Tiko 3DS (yazıldı, **doğrulanmadı**), kapıda ödeme, havale,
WhatsApp ile sipariş.

**Hesap** — kayıt/giriş/parola sıfırlama, siparişlerim (durum çizgisi,
tekrarla), adres defteri, favoriler, bilgilerim, KVKK (veri indir / hesap sil).
**Misafir alışverişi korundu**; hesap isteğe bağlı. Sipariş sorgulama:
numara + telefon/e-posta.

**Panel** — 16 Filament kaynağı (hepsi Türkçe), elle sipariş açma, iade
(tam/kısmi + stoğa iade), raporlar (dönem karşılaştırmalı), müşteriler,
site ayarları, yazdırılabilir belgeler (sipariş fişi, kurye teslim fişi,
günün teslimat listesi — ek PDF kütüphanesi yok, baskı CSS'i).

**E-posta** — sipariş alındı / durum değişti / ödeme alındı / ekibe yeni
sipariş / test. Markalı HTML şablon. `defer()` ile yanıt sonrası gönderilir
(kuyruk işçisi gerekmez). Gönderim hatası asla siparişi bozmaz.
E-postadaki sipariş bağlantısı 60 günlük **imzalı URL** — hesap gerekmez.

**Diğer** — sitemap.xml, robots.txt, mağaza filtreleri, hızlı bakış,
düşük stok uyarısı, WhatsApp'tan stok sorgusu (tıklamalar panele düşer).

## Bilinmesi gerekenler (tuzaklar)

1. **Filament özel sayfalarda Tailwind yardımcı sınıfı kullanma.** Filament'in
   derlenmiş CSS'i yalnız kendi sınıflarını içerir; `grid`/`gap-4` çalışmaz.
   Kendi `<style>` bloğunu yaz (Raporlar sayfası böyle).
   Renk için `var(--primary-500)`, `rgb(var(...))` değil.
2. **`Auth::logout()` silinen kullanıcıyı geri yazabilir** (hatırlama jetonu
   için `save()` çağırır). Hesap silmede sıra: önce logout, sonra
   `User::whereKey($id)->delete()`.
3. **Blade `@json()` içine çok satırlı dizi yazma** — parse hatası verir.
   Değeri `@php` bloğunda hazırla.
4. **`overflow-x: clip` kullan, `hidden` değil** — `hidden` tüm `sticky`'yi bozar.
5. **`[hidden]`**, `display:grid` veren sınıflar tarafından ezilir; global
   `[hidden]{display:none!important}` kuralı var.
6. **Görsel seçerken URL'in 200 dönmesi yetmez** — içeriği tarayıcıda
   contact-sheet ile gözle doğrula (ilk denemede "orkide" diye seçilen ID
   gökkuşağı gül çıkmıştı).
7. **`x-icon` adı Filament'in blade-icons paketiyle çakışır** — bizimki `x-ay-icon`.
8. Testte `defer()` için `$this->withoutDefer()`.

## PLACEHOLDER — gerçeğiyle değiştirilecek

Hepsi **Panel › Site ayarları**'ndan:
telefon `+90 533 000 00 00` · whatsapp `905330000000` ·
`merhaba@ayparcasicicekci.com` · adres · Instagram.

Mevcut ayparcasicicekci.com bakım modundaydı, bilgi çekilemedi.

**Ürün görselleri demo**: Unsplash + Wikimedia Commons'tan indirilip
`storage/app/public/demo/` altında **yerel** tutuluyor (hotlink yok).

## Açık işler

| Öncelik | İş |
|---|---|
| 🔴 | **Ürün yorumları** — kartlarda yıldız gösteriyoruz ama sayılar tohumlanmış **sahte veri**. Ya yorum sistemi yazılmalı ya yıldızlar kaldırılmalı |
| 🔴 | **Tiko** kimlik bilgileri + entegrasyon evrakıyla alan adı doğrulaması. Alan adları yalnız `config/tiko.php`'den değişir. `TIKO_ENABLED=false` iken kasada kart seçeneği hiç görünmez |
| 🟡 | **Canlıda SMTP** (yerelde `MAIL_MAILER=log`). Brevo hesabı var. Bağlayınca Ayarlar › "Test e-postası gönder" ile doğrula |
| 🟡 | Gerçek ürün fotoğrafları ve iletişim bilgileri |
| 🟢 | KDV/vergi motoru, mali fatura (muhasebe gerektiriyorsa) |
| 🟢 | Turhost/MySQL deploy paketi |

## Git

Uzak depo **YOK**, canlıya **çıkılmadı**. Ana commit'ler:

| | |
|---|---|
| `9a231b8` | İlk sürüm |
| `9081e2f` | Ürün kartı düzeni + hızlı bakış |
| `b05871a` | Müşteri hesap bölümü |
| `ea6d2ff` | Woo karşılaştırmasındaki kısa vadeli eksikler |
| `2334d2b` | Sipariş e-postaları |
| `8466f38` | Footer düzenlemesi + ajans imzası |
