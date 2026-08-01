<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'min_total' => 'decimal:2',
        'free_delivery' => 'boolean',
        'exclude_sale_items' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    // --- Uygunluk ---------------------------------------------------------

    /**
     * Kupon bu sepet satırında geçerli mi?
     *
     * @param  array{product: Product}  $line
     */
    public function appliesToLine(array $line): bool
    {
        /** @var Product $product */
        $product = $line['product'];

        if ($this->exclude_sale_items && $product->discount_percent) {
            return false;
        }

        if ($this->applies_to === 'products') {
            return $this->products->contains('id', $product->id);
        }

        if ($this->applies_to === 'categories') {
            $allowed = $this->categories->pluck('id');

            return $product->categories->pluck('id')->intersect($allowed)->isNotEmpty();
        }

        return true;
    }

    /**
     * İndirimin uygulanacağı tutar. Kısıt yoksa sepet ara toplamına eşittir.
     *
     * @param  Collection<int, array<string, mixed>>  $lines
     */
    public function eligibleSubtotal(Collection $lines): float
    {
        if ($this->applies_to === 'all' && ! $this->exclude_sale_items) {
            return round((float) $lines->sum('line_total'), 2);
        }

        $this->loadMissing('products', 'categories');

        return round((float) $lines->filter(fn ($line) => $this->appliesToLine($line))->sum('line_total'), 2);
    }

    /** Bu kupon bu müşteri tarafından kaç kez kullanıldı? */
    public function usedBy(?int $userId, ?string $email): int
    {
        if (! $userId && ! $email) {
            return 0;
        }

        return Order::query()
            ->where('coupon_id', $this->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
                if ($email) {
                    $q->orWhereRaw('LOWER(customer_email) = ?', [mb_strtolower($email)]);
                }
            })
            ->count();
    }

    /**
     * @return string|null Hata mesajı; null ise kupon geçerli.
     */
    public function validationError(
        float $subtotal,
        ?float $eligibleSubtotal = null,
        ?int $userId = null,
        ?string $email = null,
    ): ?string {
        $eligibleSubtotal ??= $subtotal;

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
            return 'Bu kupon en az '.money($this->min_total).' sepet tutarında geçerli.';
        }

        if ($this->allowedEmails() && ! in_array(mb_strtolower((string) $email), $this->allowedEmails(), true)) {
            return 'Bu kupon yalnızca belirli e-posta adresleri için geçerli.';
        }

        if ($this->per_user_limit !== null && $this->usedBy($userId, $email) >= $this->per_user_limit) {
            return 'Bu kuponu kullanma hakkınız doldu.';
        }

        if ($eligibleSubtotal <= 0) {
            return $this->exclude_sale_items && $this->applies_to === 'all'
                ? 'Bu kupon indirimli ürünlerde geçmiyor.'
                : 'Bu kupon sepetinizdeki ürünlerde geçmiyor.';
        }

        return null;
    }

    /** @return array<string>|null */
    public function allowedEmails(): ?array
    {
        if (blank($this->allowed_emails)) {
            return null;
        }

        $list = collect(preg_split('/[\s,;]+/', (string) $this->allowed_emails))
            ->filter()
            ->map(fn ($e) => mb_strtolower(trim($e)))
            ->values()
            ->all();

        return $list ?: null;
    }

    /** İndirim tutarı — yalnızca uygun satırlar üzerinden hesaplanır. */
    public function discountFor(float $eligibleSubtotal): float
    {
        $discount = $this->type === 'percent'
            ? $eligibleSubtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $eligibleSubtotal), 2);
    }

    public function getRestrictionLabelAttribute(): string
    {
        return match ($this->applies_to) {
            'products' => 'Seçili ürünler',
            'categories' => 'Seçili kategoriler',
            default => 'Tüm ürünler',
        };
    }
}
