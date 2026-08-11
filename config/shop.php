<?php

/*
 * Dükkâna özgü ayarlar.
 *
 * Bu değerler BİLEREK config üzerinden okunuyor, doğrudan env() ile değil:
 * Laravel config önbelleğe alındığında (`artisan config:cache` / `optimize`)
 * .env dosyasını bir daha YÜKLEMEZ, dolayısıyla config dosyalarının dışında
 * çağrılan env() her zaman null döner. Canlıda deploy her seferinde optimize
 * çalıştırdığı için bu sessiz bir hataya dönüşüyordu.
 */
return [

    /*
     * İlk kurulumda açılacak yönetici hesabı. `php artisan admin:olustur`
     * bunları kullanır; hesap zaten varsa dokunmaz. Panele girdikten sonra
     * .env içindeki ADMIN_PASSWORD satırını silin.
     */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Yönetici'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /* Panelden ayar girilmemişse kullanılacak WhatsApp numarası. */
    'whatsapp' => env('SHOP_WHATSAPP', '905488000000'),

];
