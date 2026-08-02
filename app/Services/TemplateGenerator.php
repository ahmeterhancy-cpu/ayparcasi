<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use Illuminate\Support\Collection;

/**
 * Mevcut kataloğdan ürün şablonu türetir.
 *
 * Gruplama alt kategorilere göre yapılır: "Buketler", "Orkideler" gibi
 * ürün tipleri. Katalogda ürün tipi ile özel gün kategorilerini birbirinden
 * ayıracak yapısal bir işaret YOK (ikisi de alt kategori, ikisinde de ürün
 * başına ortalama bir kategori düşüyor) — bu yüzden hangi dalın atlanacağı
 * çağıranın kararı, tahmin edilmiyor.
 */
class TemplateGenerator
{
    /**
     * @param  array<int>  $excludeParentIds  Bu üst kategorilerin altındakiler atlanır
     * @return array{created: array<string>, updated: array<string>, skipped: array<string>}
     */
    public function generate(array $excludeParentIds = [], int $minProducts = 2, bool $refresh = false): array
    {
        $result = ['created' => [], 'updated' => [], 'skipped' => []];

        $categories = Category::query()
            ->whereNotNull('parent_id')
            ->when($excludeParentIds, fn ($q) => $q->whereNotIn('parent_id', $excludeParentIds))
            ->orderBy('position')
            ->get();

        foreach ($categories as $category) {
            $products = Product::query()
                ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
                ->with('addons', 'variants')
                ->get();

            if ($products->count() < $minProducts) {
                $result['skipped'][] = $category->name.' ('.$products->count().' ürün)';

                continue;
            }

            $existing = ProductTemplate::where('name', $category->name)->first();

            if ($existing && ! $refresh) {
                $result['skipped'][] = $category->name.' (şablon zaten var)';

                continue;
            }

            $attributes = $this->attributesFor($category, $products);

            if ($existing) {
                $existing->update($attributes);
                $result['updated'][] = $category->name;

                continue;
            }

            ProductTemplate::create($attributes);
            $result['created'][] = $category->name;
        }

        return $result;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function attributesFor(Category $category, Collection $products): array
    {
        $representative = $this->representative($products);

        return [
            'name' => $category->name,
            'short_description' => $representative->short_description,
            'description' => $representative->description,
            'contents' => $this->mostCommon($products->pluck('contents')),
            'care_notes' => $this->mostCommon($products->pluck('care_notes')),
            // Rozet bilerek boş: "Çok satan" yeni ürüne kendiliğinden yapışmasın.
            'badge' => null,
            'price' => $this->medianPrice($products),
            'track_stock' => (bool) $this->mostCommon($products->pluck('track_stock')),
            'stock' => 0,
            'same_day' => (bool) $this->mostCommon($products->pluck('same_day')),
            'category_ids' => [$category->id],
            // Yalnız gruptaki HER üründe bulunan ek ürünler; birinde olup
            // ötekinde olmayan seçenek şablona girmez.
            'addon_ids' => $this->sharedAddonIds($products),
            'variants' => $this->variantsOf($representative),
            'position' => (int) $category->position,
        ];
    }

    /**
     * Grubu en iyi temsil eden ürün: önce boy seçeneği olanlar, sonra
     * fiyatı ortancaya en yakın olan.
     *
     * @param  Collection<int, Product>  $products
     */
    private function representative(Collection $products): Product
    {
        $withVariants = $products->filter(fn (Product $p) => $p->variants->isNotEmpty());
        $pool = $withVariants->isNotEmpty() ? $withVariants : $products;

        $median = $this->medianPrice($pool);

        return $pool->sortBy(fn (Product $p) => abs((float) $p->display_price - $median))->first();
    }

    /** @param  Collection<int, Product>  $products */
    private function medianPrice(Collection $products): float
    {
        $prices = $products->map(fn (Product $p) => (float) $p->display_price)->sort()->values();

        if ($prices->isEmpty()) {
            return 0.0;
        }

        $middle = intdiv($prices->count(), 2);

        return $prices->count() % 2
            ? $prices[$middle]
            : round(($prices[$middle - 1] + $prices[$middle]) / 2, 2);
    }

    /** @param  Collection<int, Product>  $products */
    private function sharedAddonIds(Collection $products): array
    {
        return $products
            ->map(fn (Product $p) => $p->addons->pluck('id')->all())
            ->reduce(fn (?array $carry, array $ids) => $carry === null ? $ids : array_intersect($carry, $ids)) ?? [];
    }

    /** @return array<array<string, mixed>> */
    private function variantsOf(Product $product): array
    {
        return $product->variants
            ->map(fn ($variant) => [
                'name' => $variant->name,
                'description' => $variant->description,
                'price' => (float) $variant->price,
                'stock' => 0,
                'is_default' => (bool) $variant->is_default,
            ])
            ->values()
            ->all();
    }

    /** Gruptaki en sık görülen değer. */
    private function mostCommon(Collection $values): mixed
    {
        $filtered = $values->reject(fn ($value) => $value === null || $value === '');

        if ($filtered->isEmpty()) {
            return null;
        }

        return $filtered
            ->countBy(fn ($value) => is_bool($value) ? (int) $value : $value)
            ->sortDesc()
            ->keys()
            ->first();
    }
}
