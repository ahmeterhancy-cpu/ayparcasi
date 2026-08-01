<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'restocked' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** İadeyi yapan ekip üyesi. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
