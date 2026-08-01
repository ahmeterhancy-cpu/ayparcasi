<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fee' => 'decimal:2',
        'free_over' => 'decimal:2',
        'same_day' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function feeFor(float $subtotal): float
    {
        if ($this->free_over !== null && $subtotal >= (float) $this->free_over) {
            return 0.0;
        }

        return (float) $this->fee;
    }
}
