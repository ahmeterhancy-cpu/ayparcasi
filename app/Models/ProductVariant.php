<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Kasada tahsil edilecek fiyat. İndirim penceresi ürün seviyesinde
     * tanımlıdır; pencere dışındaysa boyun eski fiyatından satılır.
     */
    public function effectivePriceFor(?Product $product = null): float
    {
        $product ??= $this->product;

        if ($product && ! $product->sale_active && $this->compare_at_price) {
            return (float) $this->compare_at_price;
        }

        return (float) $this->price;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->effectivePriceFor();
    }

    public function getIsOrderableAttribute(): bool
    {
        if (! $this->product?->track_stock) {
            return true;
        }

        return $this->stock > 0;
    }
}
