<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'gallery' => 'array',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Vitrinde gösterilen yorumlar — yalnızca onaylananlar. */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->approved()->latest('id');
    }

    /**
     * Puan dağılımı: 5 yıldızdan 1'e kadar kaç yorum var.
     *
     * @return array<int, int>
     */
    public function ratingBreakdown(): array
    {
        $counts = $this->reviews()
            ->approved()
            ->selectRaw('rating, COUNT(*) as c')
            ->groupBy('rating')
            ->pluck('c', 'rating');

        return collect(range(5, 1))
            ->mapWithKeys(fn (int $star) => [$star => (int) ($counts[$star] ?? 0)])
            ->all();
    }

    /**
     * Bu müşteriye yorum hakkı veren sipariş.
     * Yalnızca teslim edilmiş ve bu ürünü içeren sipariş sayılır.
     */
    public function reviewableOrderFor(?User $user): ?Order
    {
        if (! $user) {
            return null;
        }

        return Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $this->id))
            ->latest('id')
            ->first();
    }

    /** Müşterinin bu ürüne daha önce yazdığı yorum (onay beklese de döner). */
    public function reviewBy(?User $user): ?Review
    {
        if (! $user) {
            return null;
        }

        return $this->reviews()->where('user_id', $user->id)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /** Şu an gerçekten indirimde olanlar (tarih penceresi dahil). */
    public function scopeOnSale($query)
    {
        return $query
            ->whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price')
            ->where(fn ($q) => $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now()));
    }

    /** Sipariş verilebilir olanlar. */
    public function scopeInStock($query)
    {
        return $query->where(fn ($q) => $q
            ->where('track_stock', false)
            ->orWhere('stock', '>', 0)
            ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)));
    }

    /**
     * İndirim şu an geçerli mi?
     * Tarih verilmemişse indirim süresizdir; verilmişse yalnızca o aralıkta geçer.
     */
    public function getSaleActiveAttribute(): bool
    {
        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return false;
        }

        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Kasada gerçekten tahsil edilecek fiyat.
     * İndirim penceresi dışındaysa üstü çizili (normal) fiyattan satılır.
     */
    public function getEffectivePriceAttribute(): float
    {
        if (! $this->sale_active && $this->compare_at_price) {
            return (float) $this->compare_at_price;
        }

        return (float) $this->price;
    }

    /** @return Collection<int, ProductVariant> */
    private function activeVariants()
    {
        return $this->relationLoaded('variants')
            ? $this->variants->where('is_active', true)
            : $this->variants()->where('is_active', true)->get();
    }

    /** Varyantlı üründe gösterilecek "başlangıç" fiyatı. */
    public function getDisplayPriceAttribute(): float
    {
        $variants = $this->activeVariants();

        if ($variants->isNotEmpty()) {
            return (float) $variants->map(fn (ProductVariant $v) => $v->effectivePriceFor($this))->min();
        }

        return $this->effective_price;
    }

    public function getDisplayCompareAtAttribute(): ?float
    {
        if (! $this->sale_active) {
            return null; // indirim penceresi dışında üstü çizili fiyat gösterilmez
        }

        $variants = $this->activeVariants();

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
