<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Yapım aşamasında" perdesi — panelden açılıp kapatılır.
 *
 * Yalnızca `web` grubuna eklenir. Filament paneli kendi middleware yığınını
 * kurduğu için yönetim tarafı perde açıkken de her zaman erişilebilir kalır.
 */
class MaintenanceMode
{
    /**
     * Perde açıkken de çalışması gereken yollar.
     *
     * Ödeme ve sipariş yolları bilerek açık: perdeyi indirdiğiniz anda
     * bankada ödemesi süren bir sipariş varsa müşteri dönüşte yarı yolda
     * kalmasın, Tiko'nun sunucudan gelen bildirimi de düşmesin.
     */
    private const ALWAYS_OPEN = [
        'admin',
        'admin/*',
        'up',
        'livewire/*',
        'odeme/*',
        'siparis/*',
        'cikis',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Anahtar "1"/"0" olarak yazılır — boş değer kapalı sayılır.
        if (! (bool) setting('maintenance_enabled')) {
            return $next($request);
        }

        if ($request->is(...self::ALWAYS_OPEN)) {
            return $next($request);
        }

        // Perdeyi geçmenin TEK yolu ekip hesabıyla giriş yapmış olmak.
        // Daha önce ayrı bir "önizleme anahtarı" da vardı; yönetilecek
        // ikinci bir sır olmasın diye kaldırıldı.
        if ($request->user()?->isStaff()) {
            return $next($request);
        }

        /*
         * Perde ASLA önbelleğe alınmamalı. Sunucudaki LiteSpeed önbelleği ya
         * da tarayıcı bu 503'ü saklarsa, ekip hesabıyla giriş yapmış olsanız
         * bile size saklanmış perde dönüyor — site açıldıktan sonra bile
         * ziyaretçiye kapalı görünebiliyor.
         */
        return response()
            ->view('maintenance', status: Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-LiteSpeed-Cache-Control', 'no-cache');
    }
}
