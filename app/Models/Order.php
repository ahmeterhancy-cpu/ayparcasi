<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_payload' => 'array',
        'hide_sender' => 'boolean',
        'delivery_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const STATUSES = [
        'pending' => 'Yeni sipariş',
        'confirmed' => 'Onaylandı',
        'preparing' => 'Hazırlanıyor',
        'on_the_way' => 'Yolda',
        'delivered' => 'Teslim edildi',
        'cancelled' => 'İptal',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Ödenmedi',
        'paid' => 'Ödendi',
        'failed' => 'Başarısız',
        'refunded' => 'İade edildi',
    ];

    public const PAYMENT_METHODS = [
        'tiko' => 'Kredi kartı (Tiko)',
        'cash' => 'Kapıda ödeme',
        'transfer' => 'Havale / EFT',
        'whatsapp' => 'WhatsApp ile',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Misafir siparişlerinde boştur. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public static function nextNumber(): string
    {
        $prefix = 'AP'.now()->format('ymd');
        $count = static::where('number', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
