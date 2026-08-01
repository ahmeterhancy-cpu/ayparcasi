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
