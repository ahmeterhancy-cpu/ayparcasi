<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ürünlerin CSV ile dışa/içe aktarımı.
 *
 * Kuyruk gerektirmez — dosya anında işlenir ve sonuç hemen bildirilir.
 * Küçük bir mağazada (binlerce değil, yüzlerce ürün) doğru tercih budur:
 * arka planda takılıp kalan iş olmaz.
 */
class ProductCsv
{
    /** Sütun başlıkları — dışa aktarımda yazılır, içe aktarımda beklenir. */
    public const COLUMNS = [
        'ad', 'baglanti', 'stok_kodu', 'kategoriler',
        'kisa_aciklama', 'aciklama', 'icindekiler', 'bakim',
        'fiyat', 'eski_fiyat', 'indirim_baslangic', 'indirim_bitis',
        'stok_takibi', 'stok', 'stok_durumu',
        'rozet', 'satista', 'one_cikan', 'ayni_gun', 'sira',
        'kapak_gorseli', 'galeri', 'meta_baslik', 'meta_aciklama',
    ];

    private const SEP = ';';

    public function export(): StreamedResponse
    {
        $filename = 'urunler-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel'in UTF-8 okuması için

            fputcsv($out, self::COLUMNS, self::SEP);

            Product::with('categories')->orderBy('position')->chunk(200, function ($products) use ($out) {
                foreach ($products as $p) {
                    fputcsv($out, [
                        $p->name,
                        $p->slug,
                        $p->sku,
                        $p->categories->pluck('name')->implode('|'),
                        $p->short_description,
                        $p->description,
                        $p->contents,
                        $p->care_notes,
                        $this->num($p->price),
                        $this->num($p->compare_at_price),
                        $p->sale_starts_at?->format('Y-m-d H:i'),
                        $p->sale_ends_at?->format('Y-m-d H:i'),
                        $p->track_stock ? 'evet' : 'hayir',
                        $p->stock,
                        $p->stock_status,
                        $p->badge,
                        $p->is_active ? 'evet' : 'hayir',
                        $p->is_featured ? 'evet' : 'hayir',
                        $p->same_day ? 'evet' : 'hayir',
                        $p->position,
                        $p->hero_image,
                        collect($p->gallery ?? [])->implode('|'),
                        $p->meta_title,
                        $p->meta_description,
                    ], self::SEP);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<string>}
     */
    public function import(string $path): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($path, 'r');

        if (! $handle) {
            $result['errors'][] = 'Dosya açılamadı.';

            return $result;
        }

        // BOM varsa at
        $first = fgets($handle);
        rewind($handle);
        if ($first !== false && str_starts_with($first, "\xEF\xBB\xBF")) {
            fseek($handle, 3);
        }

        $header = fgetcsv($handle, 0, self::SEP);

        if (! $header) {
            $result['errors'][] = 'Dosya boş görünüyor.';
            fclose($handle);

            return $result;
        }

        $header = array_map(fn ($h) => Str::slug(trim((string) $h), '_'), $header);

        if (! in_array('ad', $header, true)) {
            $result['errors'][] = '"ad" sütunu bulunamadı. Önce mevcut ürünleri dışa aktarıp o dosyayı düzenleyin.';
            fclose($handle);

            return $result;
        }

        $categories = Category::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);

        $row = 1;

        while (($data = fgetcsv($handle, 0, self::SEP)) !== false) {
            $row++;

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // boş satır
            }

            $line = array_combine(
                array_slice($header, 0, count($data)),
                array_slice($data, 0, count($header))
            ) ?: [];

            $name = trim((string) ($line['ad'] ?? ''));

            if ($name === '') {
                $result['skipped']++;
                $result['errors'][] = "Satır {$row}: ürün adı boş, atlandı.";

                continue;
            }

            try {
                DB::transaction(function () use ($line, $name, $categories, &$result) {
                    $slug = trim((string) ($line['baglanti'] ?? ''));
                    $sku = trim((string) ($line['stok_kodu'] ?? ''));

                    $product = null;

                    if ($slug !== '') {
                        $product = Product::where('slug', $slug)->first();
                    }

                    if (! $product && $sku !== '') {
                        $product = Product::where('sku', $sku)->first();
                    }

                    $isNew = ! $product;
                    $product ??= new Product;

                    $product->fill(array_filter([
                        'name' => $name,
                        'slug' => $slug ?: null,
                        'sku' => $sku ?: null,
                        'short_description' => $this->str($line, 'kisa_aciklama'),
                        'description' => $this->str($line, 'aciklama'),
                        'contents' => $this->str($line, 'icindekiler'),
                        'care_notes' => $this->str($line, 'bakim'),
                        'badge' => $this->str($line, 'rozet'),
                        'stock_status' => $this->str($line, 'stok_durumu'),
                        'hero_image' => $this->str($line, 'kapak_gorseli'),
                        'meta_title' => $this->str($line, 'meta_baslik'),
                        'meta_description' => $this->str($line, 'meta_aciklama'),
                    ], fn ($v) => $v !== null));

                    // Sayısal ve mantıksal alanlar — boş bırakılırsa mevcut değer korunur
                    foreach (['fiyat' => 'price', 'eski_fiyat' => 'compare_at_price', 'stok' => 'stock', 'sira' => 'position'] as $csv => $col) {
                        if (isset($line[$csv]) && trim((string) $line[$csv]) !== '') {
                            $product->{$col} = $this->parseNumber($line[$csv]);
                        }
                    }

                    foreach (['stok_takibi' => 'track_stock', 'satista' => 'is_active', 'one_cikan' => 'is_featured', 'ayni_gun' => 'same_day'] as $csv => $col) {
                        if (isset($line[$csv]) && trim((string) $line[$csv]) !== '') {
                            $product->{$col} = $this->parseBool($line[$csv]);
                        }
                    }

                    foreach (['indirim_baslangic' => 'sale_starts_at', 'indirim_bitis' => 'sale_ends_at'] as $csv => $col) {
                        if (isset($line[$csv])) {
                            $value = trim((string) $line[$csv]);
                            $product->{$col} = $value !== '' ? $value : null;
                        }
                    }

                    if (isset($line['galeri'])) {
                        $gallery = collect(explode('|', (string) $line['galeri']))
                            ->map(fn ($g) => trim($g))->filter()->values()->all();
                        $product->gallery = $gallery ?: null;
                    }

                    if ($isNew) {
                        $product->is_active ??= true;
                        $product->track_stock ??= true;
                    }

                    $product->save();

                    // Kategoriler
                    if (isset($line['kategoriler']) && trim((string) $line['kategoriler']) !== '') {
                        $names = collect(explode('|', (string) $line['kategoriler']))
                            ->map(fn ($n) => trim($n))->filter();

                        $ids = $names
                            ->map(fn ($n) => $categories[mb_strtolower($n)] ?? null)
                            ->filter()
                            ->values();

                        $unknown = $names->filter(fn ($n) => ! isset($categories[mb_strtolower($n)]));

                        if ($unknown->isNotEmpty()) {
                            $result['errors'][] = $product->name.': şu kategoriler bulunamadı — '.$unknown->implode(', ');
                        }

                        if ($ids->isNotEmpty()) {
                            $product->categories()->sync($ids);
                        }
                    }

                    $isNew ? $result['created']++ : $result['updated']++;
                });
            } catch (\Throwable $e) {
                $result['skipped']++;
                $result['errors'][] = "Satır {$row} ({$name}): ".$e->getMessage();
            }
        }

        fclose($handle);

        return $result;
    }

    private function str(array $line, string $key): ?string
    {
        if (! array_key_exists($key, $line)) {
            return null;
        }

        $value = trim((string) $line[$key]);

        return $value !== '' ? $value : null;
    }

    /** "1.250,50" ve "1250.50" biçimlerinin ikisini de kabul et. */
    private function parseNumber(mixed $value): float
    {
        $value = trim((string) $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }

        return (float) str_replace(',', '.', $value);
    }

    private function parseBool(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['evet', 'e', '1', 'true', 'yes', 'var'], true);
    }

    private function num(mixed $value): string
    {
        return $value === null ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
