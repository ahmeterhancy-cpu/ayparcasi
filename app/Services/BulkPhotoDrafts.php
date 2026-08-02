<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Toplu fotoğraftan taslak ürün üretir.
 *
 * Çekimden çıkan fotoğrafları bir kerede atıp adı/fiyatı listede
 * doldurmak için. Ürünler bilerek YAYINDAN KALDIRILMIŞ açılır — adı ve
 * fiyatı girilmemiş bir ürün vitrine düşmesin.
 */
class BulkPhotoDrafts
{
    /**
     * @param  array<UploadedFile>  $files
     * @return array{created: int, names: array<string>}
     */
    public function create(array $files, ?ProductTemplate $template = null, bool $nameFromFilename = true): array
    {
        $position = (int) Product::max('position');
        $names = [];

        foreach (array_values($files) as $i => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $name = $nameFromFilename
                ? $this->nameFromFilename($file->getClientOriginalName())
                : '';

            if ($name === '') {
                $name = 'Yeni ürün '.($i + 1);
            }

            $product = Product::create([
                'name' => $name,
                'slug' => Product::uniqueSlug($name),
                'price' => $template?->price ?? 0,
                'hero_image' => Storage::disk('public')->putFile('products', $file),
                'is_active' => false,
                'position' => ++$position,
            ]);

            $template?->applyTo($product);

            $names[] = $product->name;
        }

        return ['created' => count($names), 'names' => $names];
    }

    /**
     * "kirmizi-gul-buketi.jpg" -> "Kirmizi Gul Buketi"
     * Telefonun verdiği IMG_1234 / WhatsApp gibi anlamsız adlar atılır.
     */
    private function nameFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = str_replace(['_', '-', '.'], ' ', $base);
        $base = trim(preg_replace('/\s+/u', ' ', $base));

        if ($base === '' || preg_match('/^(img|image|photo|dsc|pxl|screenshot|whatsapp|20\d{6})/i', $base)) {
            return '';
        }

        return Str::title(Str::limit($base, 80, ''));
    }
}
