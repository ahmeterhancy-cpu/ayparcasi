<?php

namespace App\Models;

use App\Services\OrderMailer;
use App\Services\OrderStock;
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
        'refunded_total' => 'decimal:2',
        'stock_reserved' => 'boolean',
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

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            // Sipariş iptal edilince düşülen stok otomatik geri gelsin.
            // Model olayında olduğu için panelden, toplu işlemden, koddan —
            // hangi yoldan iptal edilirse edilsin çalışır.
            if ($order->wasChanged('status') && $order->status === 'cancelled') {
                app(OrderStock::class)->restore($order->load('items'));
            }

            // Bildirimler yanıt döndükten sonra gönderilir; kimseyi bekletmez.
            if ($order->wasChanged('status')) {
                defer(fn () => app(OrderMailer::class)->statusChanged($order));
            }

            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                defer(fn () => app(OrderMailer::class)->paid($order));
            }
        });
    }

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

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class)->latest('id');
    }

    /** Henüz iade edilmemiş tutar. */
    public function getRefundableAttribute(): float
    {
        return max(0, round((float) $this->total - (float) $this->refunded_total, 2));
    }

    public function getIsFullyRefundedAttribute(): bool
    {
        return (float) $this->total > 0 && $this->refundable <= 0;
    }

    /**
     * Kalemlerden tutarları yeniden hesapla.
     * Panelden elle açılan/düzenlenen siparişlerde kullanılır.
     */
    public function recalculate(): void
    {
        $this->load('items.product');

        foreach ($this->items as $item) {
            $changed = [];

            if (blank($item->name) && $item->product) {
                $changed['name'] = $item->product->name;
                $changed['image'] = $item->product->hero_image;
            }

            $lineTotal = round((float) $item->unit_price * max(1, (int) $item->quantity), 2);

            if ((float) $item->line_total !== $lineTotal) {
                $changed['line_total'] = $lineTotal;
            }

            if ($changed) {
                $item->forceFill($changed)->save();
            }
        }

        $subtotal = round((float) $this->items()->sum('line_total'), 2);

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => max(0, round($subtotal - (float) $this->discount + (float) $this->delivery_fee, 2)),
        ])->save();
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
