<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    /** Bu adresi varsayılan yap, diğerlerinin işaretini kaldır. */
    public function makeDefault(): void
    {
        static::where('user_id', $this->user_id)
            ->whereKeyNot($this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function getSummaryAttribute(): string
    {
        return trim(($this->zone?->name ? $this->zone->name.' · ' : '').$this->address);
    }
}
