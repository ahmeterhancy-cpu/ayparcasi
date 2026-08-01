<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /**
     * Admin panelinden yönetilen site ayarını okur.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $value = Setting::get($key, null);

        return ($value === null || $value === '') ? $default : $value;
    }
}

if (! function_exists('money')) {
    /**
     * 1250 -> "1.250 TL" ; 1250.5 -> "1.250,50 TL"
     */
    function money(float|int|string|null $amount, bool $withCurrency = true): string
    {
        $amount = (float) ($amount ?? 0);
        $decimals = fmod($amount, 1) === 0.0 ? 0 : 2;
        $formatted = number_format($amount, $decimals, ',', '.');

        return $withCurrency ? $formatted.' TL' : $formatted;
    }
}

if (! function_exists('wa_link')) {
    /**
     * Mağazanın WhatsApp numarasına ön-doldurulmuş mesaj bağlantısı.
     */
    function wa_link(string $message = '', ?string $phone = null): string
    {
        $phone = preg_replace('/\D+/', '', $phone ?: (string) setting('whatsapp', env('SHOP_WHATSAPP', '905488000000')));

        return 'https://wa.me/'.$phone.($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}

if (! function_exists('ship_countdown')) {
    /**
     * Aynı gün gönderime ne kadar kaldığı. Ürün kartlarındaki
     * "Bugün teslim için 16 saat 38 dakika" satırını besler.
     *
     * @return array{open: bool, minutes: int, label: ?string}
     */
    function ship_countdown(): array
    {
        $cutoffHour = (int) setting('same_day_cutoff_hour', 15);

        $now = now();
        $cutoff = $now->copy()->startOfDay()->addHours($cutoffHour);
        $minutes = $now->lt($cutoff) ? (int) $now->diffInMinutes($cutoff) : 0;

        if ($minutes <= 0) {
            return ['open' => false, 'minutes' => 0, 'label' => null];
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return [
            'open' => true,
            'minutes' => $minutes,
            'label' => $hours > 0 ? $hours.' saat '.$rest.' dakika' : $rest.' dakika',
        ];
    }
}

if (! function_exists('img_url')) {
    /**
     * Görsel alanı hem tam URL (seed'lenmiş uzak görseller) hem de
     * storage yolu (admin panelinden yüklenenler) olabilir.
     */
    function img_url(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
