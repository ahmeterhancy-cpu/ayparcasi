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

        // Ekip, site kapalıyken de vitrini normal şekilde gezebilir.
        if ($request->user()?->isStaff()) {
            return $next($request);
        }

        $key = trim((string) setting('maintenance_bypass_key', ''));

        if ($key !== '' && $request->query('anahtar') === $key) {
            // Anahtar bir kez okunur, oturuma yazılır; adres çubuğunda
            // dolaşmasın diye temiz adrese geri gönderiyoruz.
            $request->session()->put('maintenance_bypass', $key);

            return redirect()->to($request->url());
        }

        if ($key !== '' && $request->session()->get('maintenance_bypass') === $key) {
            return $next($request);
        }

        return response()
            ->view('maintenance', status: Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600');
    }
}
