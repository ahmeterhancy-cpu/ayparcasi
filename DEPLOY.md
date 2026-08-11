# Ay Parçası — cPanel'e Git ile yükleme

Bu belge siteyi Turhost/cPanel'e **Git Version Control** ile çıkarmayı anlatır.
Bir kez kurulur; sonrasında her yayın "Deploy HEAD Commit" düğmesine basmaktan ibarettir.

Yükleme öncesi kapatılması gereken içerik eksikleri için sonundaki
[Canlıya çıkmadan önce](#canlıya-çıkmadan-önce) bölümüne bakın.

---

## 0. Neyin nerede durduğu

Bu pakette alan adının kök dizini değiştirilemiyor: her şey `public_html`
içinde olmak zorunda. Yerleşim buna göre kuruldu.

```
/home/aypa8479/
├── repositories/
│   └── ayparcasi/            ← cPanel'in klonladığı depo (deploy buradan çalışır)
└── public_html/              ← alan adının kök dizini
    ├── index.php             ← deploy/cpanel/index.php'nin kopyası
    ├── .htaccess             ← Laravel'in yönlendirme kuralları
    ├── build/ img/ css/ js/ fonts/ favicon.ico ...   ← public/ içeriği
    ├── storage → ayparcasi_app/storage/app/public    ← sembolik bağ
    └── ayparcasi_app/        ← uygulamanın tamamı
        ├── .htaccess         ← KLASÖRÜ WEB'E KAPATIR (aşağıyı okuyun)
        ├── .env              ← elle oluşturulur, git'e girmez
        ├── storage/          ← yüklenen görseller, günlükler (deploy dokunmaz)
        └── app/ config/ routes/ vendor/ public/ ...
```

> ### ⚠ Bunu atlarsanız site sızdırır
>
> `ayparcasi_app/` klasörü `public_html` içinde olduğu için tarayıcıdan
> erişilebilir bir adrestedir. İçindeki `.htaccess` klasörü tamamen kapatır;
> o dosya olmazsa **`.env` dosyanız — veritabanı parolanız, e-posta parolanız,
> `APP_KEY` — düz metin olarak indirilebilir.**
>
> Deploy her seferinde bu dosyayı yeniden koyar (`.cpanel.yml`, 2. adım).
> **Kurulumdan sonra bir kez elle doğrulayın**, 403 dönmeli:
>
> ```bash
> curl -I https://ALANADI/ayparcasi_app/.env
> ```
>
> 200 dönerse siteyi hemen kapatın (Ayarlar → Yapım aşamasında), sunucunun
> `.htaccess` dosyalarını okuduğundan emin olun, sonra `APP_KEY` ile bütün
> parolaları değiştirin.

Uygulamanın kendi `public/` dizini de `ayparcasi_app` altında duruyor;
silmeyin — Vite varlık listesi (`build/manifest.json`) oradan okunuyor.

## 1. Depo

Depo hazır ve **private**:

```
https://github.com/ahmeterhancy-cpu/ayparcasi
```

Varsayılan dal `main`. `vendor/` ve `node_modules/` depoya girmez;
`public/build` **bilerek girer** (sunucuda node yok).
Ön yüzü her değiştirişinizde:

```bash
npm run build
git add public/build && git commit -m "chore: varlıkları derle"
```

## 2. cPanel tarafı

### 2-A. Veritabanı

cPanel → **MySQL® Veritabanları**:

1. Veritabanı oluştur: `kullanici_ayparcasi`
2. Kullanıcı oluştur, güçlü bir parola ver
3. Kullanıcıyı veritabanına ekle, **ALL PRIVILEGES** seç

### 2-B. Depoyu klonla

Depo **public**. cPanel adres içinde parola kabul etmiyor ("The clone URL
cannot include a password") ve bu hesapta *SSH Access* olmadığı için deploy
key de üretilemiyor — private depoyu klonlamanın yolu kalmamıştı. Bu yüzden
depo herkese açık.

cPanel → **Git Version Control** → *Create*:

- **Clone a Repository**: açık
- **Clone URL**: `https://github.com/ahmeterhancy-cpu/ayparcasi.git`
- **Repository Path**: `repositories/ayparcasi`
- **Repository Name**: `ayparcasi`

Önceki denemelerden kalan bir `repositories/ayparcasi` klasörü varsa önce
silin — cPanel dolu bir dizine klonlamaz.

> **Depo açık olduğu için:** kaynak koda hiçbir parola, anahtar ya da
> müşteri verisi girmemeli. `.env` zaten `.gitignore`'da ve geçmişte de hiç
> commit'lenmedi (doğrulandı). Seeder'daki demo parolaları da kaldırıldı.
>
> Ama **git geçmişi de okunabilir**: eski commit'lerde geçen `ayparcasi2026`
> artık yanmış sayılır, canlıda kullanmayın. Yereldeki kurulum için zararsız
> (yalnız 127.0.0.1'den erişiliyor).

### 2-C. `.cpanel.yml` dosyasını kendi hesabınıza göre düzeltin

Depodaki `.cpanel.yml` içinde üç satır var:

```yaml
- export WEBROOT=/home/aypa8479/public_html
- export APPPATH=/home/aypa8479/public_html/ayparcasi_app
- export PHPBIN=/opt/cpanel/ea-php83/root/usr/bin/php
```

Kullanıcı adı (`aypa8479`) doğrulandı, o satırlar hazır. Kontrol etmeniz
gereken tek satır **`PHPBIN`**: cPanel → *MultiPHP Manager*'da alan adı için
hangi sürüm seçiliyse ikilisi o olmalı. PHP 8.4 seçiliyse `ea-php83` yerine
`ea-php84` yazın. **Proje PHP 8.3 ve üstünü ister** — seçili sürüm 8.2 ya da
altındaysa önce MultiPHP'den yükseltin, yoksa composer kurulumu reddeder.

Düzeltmeyi yerelde yapıp `git push` edin; sunucuda dosya düzenlemeyin,
sonraki deploy üzerine yazar.

### 2-D. İlk deploy

Git Version Control → deponun yanındaki **Manage** → **Deploy HEAD Commit**.

İlk deploy `.env` olmadığı için `artisan` adımlarını atlayacak — normal,
3. bölümde `.env`'i oluşturup deploy'u tekrarlıyoruz.

### 2-E. Composer sunucuda yoksa

Deploy günlüğünde **`COMPOSER BULUNAMADI`** yazıyorsa sunucuda composer
yok demektir; `vendor/` dizinini depoya koymak gerekir. Yerelde:

```bash
composer install --no-dev --optimize-autoloader   # dev paketleri çıkar
# .gitignore içindeki "/vendor" satırını silin
git add -f vendor && git commit -m "chore: vendor'ı depoya al (sunucuda composer yok)"
git push
composer install                                   # yerelde dev paketleri geri al
```

Bu yol çalışır ama bedeli var: depo şişer ve her paket güncellemesinde
`--no-dev` ile kurup yeniden commit'lemeniz gerekir. Önce composer'ın
gerçekten yok olduğundan emin olun — hosting desteğine sormaya değer.

## 3. Sunucuda tek seferlik ayarlar (terminal olmadan)

SSH/Terminal olmadığı için `artisan` komutlarını elle çalıştıramıyoruz.
Bunların hepsini deploy görevleri (`.cpanel.yml`) çalıştırıyor. Size kalan
tek iş `.env` dosyasını oluşturmak.

### 3-A. `.env` dosyası

cPanel → **Dosya Yöneticisi** → sağ üst *Settings* → **Show Hidden Files**
açık olsun. `public_html/ayparcasi_app/` klasörüne girin:

1. `.env.production.example` dosyasını seçin → **Copy** → adı `.env` olsun
   (ya da *+ File* ile `.env` oluşturup içeriği yapıştırın)
2. `.env` dosyasını seçip **Edit** ile açın, şunları doldurun:

```dotenv
APP_URL=https://ayparcasicicekci.com
APP_KEY=base64:BURAYA-ANAHTAR

DB_DATABASE=aypa8479_ayparcasi
DB_USERNAME=aypa8479_ayparcasi
DB_PASSWORD=veritabanı-parolanız

MAIL_USERNAME=merhaba@ayparcasicicekci.com
MAIL_PASSWORD=posta-parolanız

# Yönetici hesabı ilk deploy'da bundan açılır (en az 10 karakter).
# Panele girdikten sonra bu iki satırı SİLİN.
ADMIN_NAME="Ahmet"
ADMIN_EMAIL=admin@ayparcasicicekci.com
ADMIN_PASSWORD=uzun-ve-guclu-bir-parola
```

`APP_KEY` için `php artisan key:generate` çalıştıramıyoruz; anahtar
yerelde üretilip buraya yapıştırılır. **Anahtarı sonradan değiştirmeyin** —
şifrelenmiş oturum verileri okunamaz hâle gelir.

### 3-B. Deploy'u tekrar çalıştırın

`.env` hazır olduktan sonra Git Version Control → **Deploy HEAD Commit**.
Bu turda göçler, önbellek ve yönetici hesabı oluşur.

Deploy günlüğünü aynı ekranda görebilirsiniz. Aranacaklar:

| Günlükte | Anlamı |
|---|---|
| `COMPOSER BULUNAMADI` | 2-E'ye bakın |
| `... yönetici olarak açıldı` | panel girişi hazır |
| `zaten var, dokunulmadı` | hesap önceden açılmış, sorun yok |
| `Nothing to migrate` | veritabanı güncel |

Panele girdikten sonra `.env` içindeki `ADMIN_PASSWORD` satırını silin.

### 3-C. Güvenlik kontrolü (atlamayın)

Tarayıcıda şu adresi açın:

```
https://ayparcasicicekci.com/ayparcasi_app/.env
```

**403 / Forbidden** görmelisiniz. Dosyanın içeriği görünüyorsa 0. bölümdeki
uyarıyı okuyun — parolalarınız açıkta demektir.

### 3-D. Görseller

`storage/app/public` git'te **yok**. Ürün fotoğrafları, hero görselleri ve
tanıtım videosu ayrıca taşınır: cPanel → **Dosya Yöneticisi** →
`public_html/ayparcasi_app/storage/app/public/` altına yükleyin
(klasörü yoksa oluşturun). Toplu iş için yerelde zip'leyip yükleyip
Dosya Yöneticisi'nin *Extract* düğmesini kullanmak en hızlısı.

Sembolik bağı deploy kurduğu için dosyalar `/storage/...` adresinden görünür.

### 3-E. İzinler

Dosya Yöneticisi → klasörü seç → **Permissions**:
`ayparcasi_app/storage` ve `ayparcasi_app/bootstrap/cache` → **775**
(*Recurse into subdirectories* işaretli).

## 4. Zamanlanmış görev — şimdilik gerek yok

Projede zamanlanmış iş (`routes/console.php` boş) ve kuyruğa atılan iş
(`ShouldQueue` kullanan sınıf yok) **bulunmuyor**. Sipariş e-postaları
istek sırasında doğrudan gönderiliyor. Yani şu an cron kurmanıza gerek yok.

İleride zamanlanmış bir iş eklenirse (örneğin özel gün hatırlatması)
cPanel → **Cron Jobs** → dakikada bir:

```
* * * * * /opt/cpanel/ea-php83/root/usr/bin/php /home/aypa8479/public_html/ayparcasi_app/artisan schedule:run >> /dev/null 2>&1
```

> **E-posta uyarısı:** gönderim eşzamanlı olduğu için SMTP yavaşlarsa
> sipariş tamamlama ekranı da bekler. cPanel'in kendi posta sunucusu
> genelde hızlıdır; sipariş sonrası gecikme fark ederseniz e-postaları
> kuyruğa almak gerekir.

## 5. Sonraki yayınlar

```bash
npm run build                    # ön yüz değiştiyse
git add -A && git commit -m "..."
git push
```

Sonra cPanel → Git Version Control → **Deploy HEAD Commit**. Hepsi bu.

`.cpanel.yml` göç, önbellek ve Filament optimizasyonunu kendi çalıştırır.
`.env` ile `storage/` dizinine **dokunmaz**.

---

## Canlıya çıkmadan önce

Kod hazır; kapatılması gereken içerik eksikleri:

1. **Teslimat ücretleri** — 7 bölgenin hepsi placeholder (100 TL / 2.000 TL,
   Girne 150/2.500). Panel → Teslimat → Bölgeler.
2. **Tiko kimlik bilgileri** + evrakla alan adı doğrulaması. Gelene kadar
   `TIKO_ENABLED=false` bırakın; havale ve kapıda ödeme çalışır.
3. **E-posta kutusu** — `merhaba@ayparcasicicekci.com` gerçekten açılmalı,
   sipariş bildirimleri oraya gidiyor. Çalışma saatleri ve kapı numarası da eksik.
4. **Gerçek ürün fotoğrafları** ve dükkânın kendi tanıtım videosu.

## Sorun çıkarsa

| Belirti | Sebep |
|---|---|
| **`.env` tarayıcıdan iniyor** | `ayparcasi_app/.htaccess` yok ya da sunucu okumuyor → 0. bölüm |
| 500, boş sayfa | `.env` yok ya da `APP_KEY` boş → `php artisan key:generate` |
| Stiller gelmiyor | `public/build` commit'lenmemiş → `npm run build` + commit |
| Görseller kırık | `public_html/storage` bağı yok ya da dosyalar yüklenmemiş (3-B) |
| Ana sayfa açılıyor, alt sayfalar 404 | `public_html/.htaccess` kopyalanmamış ya da `mod_rewrite` kapalı |
| Ayar değişikliği görünmüyor | Config önbelleği eski → `php artisan optimize:clear` |
| `Class not found` | `composer install` çalışmamış → `.cpanel.yml`'deki composer yolu |
| Panel açılmıyor | `filament:optimize` sonrası → `php artisan filament:optimize-clear` |
