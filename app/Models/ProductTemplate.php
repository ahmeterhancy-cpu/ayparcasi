<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ürün şablonu — "Buket", "Orkide", "Aranjman" gibi tekrar eden ürün
 * tiplerinin hazır metni, kategorisi, ek ürünleri ve boy seti.
 *
 * Şablon ürünle ilişkili değildir: yalnızca yeni ürün açılırken alanları
 * doldurur. Sonradan şablonu değiştirmek daha önce açılmış ürünlere
 * dokunmaz — böylece "şablonu düzelttim, 40 ürün değişti" sürprizi olmaz.
 */
class ProductTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'track_stock' => 'boolean',
        'same_day' => 'boolean',
        'is_active' => 'boolean',
        'category_ids' => 'array',
        'addon_ids' => 'array',
        'variants' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Mevcut bir üründen şablon çıkarır. İlk şablonu sıfırdan yazmak yerine
     * beğendiğin ürünün üstünden almak en kısa yol — metin, kategori, ek
     * ürünler ve boylar olduğu gibi gelir.
     */
    public static function fromProduct(Product $product, ?string $name = null): self
    {
        return static::create([
            'name' => $name ?: $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'contents' => $product->contents,
            'care_notes' => $product->care_notes,
            'badge' => $product->badge,
            'price' => $product->price,
            'track_stock' => $product->track_stock,
            'stock' => 0,
            'same_day' => $product->same_day,
            'category_ids' => $product->categories->pluck('id')->all(),
            'addon_ids' => $product->addons->pluck('id')->all(),
            'variants' => $product->variants->map(fn ($variant) => [
                'name' => $variant->name,
                'description' => $variant->description,
                'price' => (float) $variant->price,
                'stock' => 0,
                'is_default' => (bool) $variant->is_default,
            ])->all(),
            'position' => (int) static::max('position') + 1,
        ]);
    }

    /** Yeni ürün formunu dolduracak alanlar. */
    public function fields(): array
    {
        return [
            'short_description' => $this->short_description,
            'description' => $this->description,
            'contents' => $this->contents,
            'care_notes' => $this->care_notes,
            'badge' => $this->badge,
            'price' => (float) $this->price,
            'track_stock' => (bool) $this->track_stock,
            'stock' => (int) $this->stock,
            'same_day' => (bool) $this->same_day,
        ];
    }

    /** Silinmiş kategori/ek ürün kimlikleri süzülür. */
    public function categoryIds(): array
    {
        return Category::whereIn('id', $this->category_ids ?? [])->pluck('id')->all();
    }

    public function addonIds(): array
    {
        return Addon::whereIn('id', $this->addon_ids ?? [])->pluck('id')->all();
    }

    /**
     * Şablonu boş bir ürüne uygular. Ürünün kendi adı, görseli ve
     * bağlantı adresi korunur — onlar ürüne özgüdür.
     */
    public function applyTo(Product $product): void
    {
        $product->forceFill(array_filter(
            $this->fields(),
            fn ($value) => $value !== null,
        ))->save();

        $product->categories()->sync($this->categoryIds());
        $product->addons()->sync($this->addonIds());

        $this->applyVariants($product);
    }

    /** Boy setini ürüne kopyalar. Ürünün mevcut boyları varsa dokunmaz. */
    public function applyVariants(Product $product): void
    {
        if (blank($this->variants) || $product->variants()->exists()) {
            return;
        }

        foreach (array_values($this->variants) as $i => $variant) {
            if (blank($variant['name'] ?? null)) {
                continue;
            }

            $product->variants()->create([
                'name' => $variant['name'],
                'description' => $variant['description'] ?? null,
                'price' => (float) ($variant['price'] ?? 0),
                'stock' => (int) ($variant['stock'] ?? 0),
                'is_default' => (bool) ($variant['is_default'] ?? false),
                'is_active' => true,
                'position' => $i,
            ]);
        }

        // Hiçbiri varsayılan işaretlenmemişse ilki varsayılan olsun,
        // yoksa vitrinde fiyat seçilemez.
        if (! $product->variants()->where('is_default', true)->exists()) {
            $product->variants()->orderBy('position')->first()?->update(['is_default' => true]);
        }
    }
}
