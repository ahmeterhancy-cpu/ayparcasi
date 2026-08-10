# Ay Parçası — cPanel'e Git ile yükleme

Bu belge siteyi Turhost/cPanel'e **Git Version Control** ile çıkarmayı anlatır.
Bir kez kurulur; sonrasında her yayın "Deploy HEAD Commit" düğmesine basmaktan ibarettir.

Yükleme öncesi kapatılması gereken içerik eksikleri için sonundaki
[Canlıya çıkmadan önce](#canlıya-çıkmadan-önce) bölümüne bakın.

---

## 0. Neyin nerede durduğu

Paylaşımlı sunucuda uygulamanın **tamamı** `public_html` içine konmaz —
`.env`, veritabanı ayarları ve kodun kendisi dışarıdan okunabilir hâle gelir.
Doğru yerleşim:

```
/home/KULLANICI/
├── ayparcasi/            ← uygulama (deploy buraya yazar)
│   ├── app/  config/  routes/  vendor/ ...
│   ├── .env              ← elle oluşturulur, git'e girmez
│   ├── storage/          ← yüklenen görseller, günlükler (deploy dokunmaz)
│   └── public/           ← alan adının kök dizini BURAYI göstermeli
├── repositories/
│   └── ayparcasi/        ← cPanel'in klonladığı depo
└── public_html/          ← ana alan adı burayı gösteriyorsa 3-B'ye bakın
```

## 1. Depoyu hazırla (yerelde)

Uzak depo henüz yok. GitHub'da **private** bir depo açıp bağlayın:

```bash
git remote add origin git@github.com:KULLANICI/ayparcasi.git
git push -u origin main
```

`vendor/` ve `node_modules/` depoya girmez; `public/build` **bilerek girer**
(sunucuda node yok). Ön yüzü her değiştirişinizde:

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

cPanel → **Git Version Control** → *Create*:

- **Clone a Repository**: açık
- **Clone URL**: GitHub deposunun SSH adresi
- **Repository Path**: `repositories/ayparcasi`

SSH anahtarı istenirse cPanel → *SSH Access* → *Manage SSH Keys* ile üretip
genel anahtarı GitHub'da **Deploy keys** olarak ekleyin (salt okunur yeter).

### 2-C. `.cpanel.yml` dosyasını kendi hesabınıza göre düzeltin

Depodaki `.cpanel.yml` içinde iki satır var:

```yaml
- export DEPLOYPATH=/home/KULLANICI/ayparcasi
- export PHPBIN=/opt/cpanel/ea-php83/root/usr/bin/php
```

- `KULLANICI` → cPanel kullanıcı adınız
- `PHPBIN` → cPanel → *MultiPHP Manager*'da seçtiğiniz sürümün ikilisi.
  PHP 8.4 seçtiyseniz `ea-php84` yazın. **Proje PHP 8.3 ve üstünü ister.**

Composer yolu farklıysa (`/usr/local/bin/composer` çalışmazsa) SSH'ta
`which composer` ile bulup `.cpanel.yml` içinde düzeltin.

### 2-D. İlk deploy

Git Version Control → deponun yanındaki **Manage** → **Deploy HEAD Commit**.

İlk deploy `.env` olmadığı için `artisan` adımlarında hata verecek — normal,
sonraki adımda düzeltiyoruz.

## 3. Sunucuda tek seferlik ayarlar

SSH ya da cPanel → **Terminal**:

```bash
cd ~/ayparcasi
cp .env.production.example .env
nano .env          # veritabanı, e-posta ve APP_URL'i doldurun
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

> `php` komutu eski sürüm veriyorsa `.cpanel.yml`'deki tam yolu kullanın:
> `/opt/cpanel/ea-php83/root/usr/bin/php artisan ...`

### 3-A. Yönetici hesabı

Demo verisi **yüklenmez** (`db:seed` çalıştırmayın — panelden yaptığınız
düzenlemeleri ezer). Yalnız yönetici hesabını açın:

```bash
php artisan tinker
```
```php
App\Models\User::create([
    'name' => 'Ahmet',
    'email' => 'admin@ayparcasicicekci.com',
    'password' => 'BURAYA-GÜÇLÜ-BİR-PAROLA',
    'role' => 'admin',
]);
```

### 3-B. Alan adının kök dizini

cPanel → **Domains** → alan adının *Document Root* alanını
`/home/KULLANICI/ayparcasi/public` yapın.

Ana alan adında bu alan kilitliyse (bazı paketlerde öyle), `public_html`
içine tek bir yönlendirici koyun — `public_html/index.php`:

```php
<?php
$app = '/home/KULLANICI/ayparcasi';
require $app.'/public/index.php';
```

ve `public/.htaccess` dosyasını `public_html/.htaccess` olarak kopyalayın.
Bu yol işe yarar ama **birinci seçenek daima daha temizdir**.

### 3-C. Görseller

`storage/app/public` git'te **yok**. Ürün fotoğrafları, hero görselleri ve
tanıtım videosu yerelden ayrıca taşınır:

- cPanel → *Dosya Yöneticisi* ile `~/ayparcasi/storage/app/public/` altına yükleyin
- ya da: `scp -r storage/app/public/* KULLANICI@sunucu:~/ayparcasi/storage/app/public/`

`php artisan storage:link` bir kez çalıştıysa dosyalar `/storage/...`
adresinden görünür.

### 3-D. İzinler

```bash
chmod -R 775 ~/ayparcasi/storage ~/ayparcasi/bootstrap/cache
```

## 4. Zamanlanmış görev — şimdilik gerek yok

Projede zamanlanmış iş (`routes/console.php` boş) ve kuyruğa atılan iş
(`ShouldQueue` kullanan sınıf yok) **bulunmuyor**. Sipariş e-postaları
istek sırasında doğrudan gönderiliyor. Yani şu an cron kurmanıza gerek yok.

İleride zamanlanmış bir iş eklenirse (örneğin özel gün hatırlatması)
cPanel → **Cron Jobs** → dakikada bir:

```
* * * * * /opt/cpanel/ea-php83/root/usr/bin/php /home/KULLANICI/ayparcasi/artisan schedule:run >> /dev/null 2>&1
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
| 500, boş sayfa | `.env` yok ya da `APP_KEY` boş → `php artisan key:generate` |
| Stiller gelmiyor | `public/build` commit'lenmemiş → `npm run build` + commit |
| Görseller kırık | `storage:link` çalışmamış ya da dosyalar yüklenmemiş (3-C) |
| Ayar değişikliği görünmüyor | Config önbelleği eski → `php artisan optimize:clear` |
| `Class not found` | `composer install` çalışmamış → `.cpanel.yml`'deki composer yolu |
| Panel açılmıyor | `filament:optimize` sonrası → `php artisan filament:optimize-clear` |
