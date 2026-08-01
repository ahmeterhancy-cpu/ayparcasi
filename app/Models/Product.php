<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'gallery' => 'array',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'track_stock' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'same_day' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug($product->name, $product->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'urun';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /** Varyantlı üründe gösterilecek "başlangıç" fiyatı. */
    public function getDisplayPriceAttribute(): float
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->where('is_active', true)->get();

        if ($variants->isNotEmpty()) {
            return (float) $variants->min('price');
        }

        return (float) $this->price;
    }

    public function getDisplayCompareAtAttribute(): ?float
    {
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->where('is_active', true)->get();

        if ($variants->isNotEmpty()) {
            // display_price en ucuz boydan gelir; üstü çizili fiyat da AYNI boydan
            // gelmeli, yoksa indirim yüzdesi olduğundan büyük çıkar.
            $cheapest = $variants->sortBy('price')->first();

            return $cheapest?->compare_at_price ? (float) $cheapest->compare_at_price : null;
        }

        return $this->compare_at_price ? (float) $this->compare_at_price : null;
    }

    public function getHasVariantsAttribute(): bool
    {
        return $this->variants()->where('is_active', true)->exists();
    }

    public function getDiscountPercentAttribute(): ?int
    {
        $compare = $this->display_compare_at;
        $price = $this->display_price;

        if (! $compare || $compare <= $price) {
            return null;
        }

        return (int) round((1 - $price / $compare) * 100);
    }

    /**
     * Etkin stok durumu. track_stock açıksa sayıdan, kapalıysa elle seçilen durumdan.
     */
    public function getStockStateAttribute(): string
    {
        if (! $this->track_stock) {
            return $this->stock_status ?: 'in_stock';
        }

        $count = $this->has_variants
            ? (int) $this->variants()->where('is_active', true)->sum('stock')
            : (int) $this->stock;

        return match (true) {
            $count <= 0 => 'out_of_stock',
            $count <= 3 => 'low',
            default => 'in_stock',
        };
    }

    public function getStockLabelAttribute(): string
    {
        return match ($this->stock_state) {
            'out_of_stock' => 'Tükendi',
            'low' => 'Son birkaç adet',
            'made_to_order' => 'Siparişe özel hazırlanır',
            default => 'Stokta',
        };
    }

    public function getIsOrderableAttribute(): bool
    {
        return $this->stock_state !== 'out_of_stock';
    }

    /** Vitrin için tüm görseller (kapak + galeri). */
    public function getImagesAttribute(): array
    {
        return array_values(array_filter(array_merge(
            [$this->hero_image],
            $this->gallery ?? []
        )));
    }

    public function getUrlAttribute(): string
    {
        return route('shop.product', $this->slug);
    }
}
