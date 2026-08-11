<?php

/*
 * public_html/index.php — cPanel yerleşimi için giriş dosyası.
 *
 * Bu paketin kısıtı: uygulama public_html DIŞINA konamıyor (alan adının
 * kök dizini değiştirilemiyor). O yüzden Laravel'in tamamı
 * public_html/ayparcasi_app/ altında duruyor, web kökünde yalnız
 * public/ içeriği ve bu dosya var.
 *
 * ayparcasi_app/.htaccess klasörü web'e tamamen kapatır (deploy her
 * seferinde yeniden koyar). O dosya olmazsa .env dışarıdan okunabilir —
 * kurulumdan sonra mutlaka doğrulayın, bkz. DEPLOY.md.
 *
 * Laravel'in kendi public/index.php dosyası olduğu gibi duruyor;
 * yerel geliştirme onu kullanmaya devam ediyor.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app_base = __DIR__.'/ayparcasi_app';

// Bakım kipi (php artisan down) — Laravel'in kendi perdesi
if (file_exists($maintenance = $app_base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app_base.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $app_base.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
