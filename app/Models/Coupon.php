<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'min_total' => 'decimal:2',
        'free_delivery' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /** @return string|null Hata mesajı; null ise kupon geçerli. */
    public function validationError(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'Bu kupon artık kullanılamıyor.';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Bu kupon henüz başlamadı.';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'Bu kuponun süresi doldu.';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Bu kuponun kullanım hakkı doldu.';
        }

        if ($this->min_total !== null && $subtotal < (float) $this->min_total) {
            return 'Bu kupon en az '.number_format((float) $this->min_total, 0, ',', '.').' TL sepet tutarında geçerli.';
        }

        return null;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $subtotal), 2);
    }
}
