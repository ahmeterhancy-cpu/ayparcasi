<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
        'replied_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Onay bekliyor',
        'approved' => 'Yayında',
        'rejected' => 'Reddedildi',
    ];

    protected static function booted(): void
    {
        // Ürünün ortalama puanı her durumda yorumlardan türetilir; elle
        // yazılmaz. Onay/red/silme hangi yoldan olursa olsun (panel, toplu
        // işlem, kod) burada yakalanır.
        static::saved(fn (Review $review) => static::syncProduct($review->product_id));
        static::deleted(fn (Review $review) => static::syncProduct($review->product_id));
    }

    /** Ürünün özet puanını onaylı yorumlardan yeniden hesapla. */
    public static function syncProduct(?int $productId): void
    {
        if (! $productId) {
            return;
        }

        $stats = static::query()
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')
            ->first();

        $count = (int) ($stats->c ?? 0);

        Product::whereKey($productId)->update([
            'rating' => $count > 0 ? round((float) $stats->a, 2) : null,
            'review_count' => $count,
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Listede "Ayşe K." gibi kısaltılmış görünür. */
    public function getDisplayNameAttribute(): string
    {
        return static::shortName($this->name);
    }

    /** Soyadı baş harfe indirir: "Ayşe Kaya" → "Ayşe K." */
    public static function shortName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $first = array_shift($parts) ?: 'Müşteri';

        if (! $parts) {
            return $first;
        }

        return $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.';
    }
}
