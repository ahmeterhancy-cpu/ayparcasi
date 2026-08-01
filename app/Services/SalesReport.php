<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Belirli bir tarih aralığı için satış özeti.
 * İptal edilen siparişler her yerde dışarıda bırakılır.
 */
class SalesReport
{
    public function __construct(
        public CarbonInterface $from,
        public CarbonInterface $to,
    ) {}

    private function orders()
    {
        return Order::query()
            ->whereBetween('created_at', [$this->from, $this->to])
            ->where('status', '!=', 'cancelled');
    }

    /** @return array{revenue: float, orders: int, average: float, delivered: int, refunded: float, items: int} */
    public function totals(): array
    {
        $row = $this->orders()
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(refunded_total),0) as refunded')
            ->first();

        $count = (int) $row->c;
        $revenue = (float) $row->revenue;

        $items = (int) OrderItem::whereIn('order_id', $this->orders()->select('id'))->sum('quantity');

        return [
            'revenue' => round($revenue, 2),
            'refunded' => round((float) $row->refunded, 2),
            'net' => round($revenue - (float) $row->refunded, 2),
            'orders' => $count,
            'average' => $count > 0 ? round($revenue / $count, 2) : 0.0,
            'delivered' => (int) $this->orders()->where('status', 'delivered')->count(),
            'items' => $items,
        ];
    }

    /** Aynı uzunlukta bir önceki dönem — karşılaştırma için. */
    public function previous(): self
    {
        $length = $this->from->diffInSeconds($this->to);

        return new self(
            $this->from->copy()->subSeconds($length + 1),
            $this->from->copy()->subSecond(),
        );
    }

    /** @return Collection<int, array{label: string, revenue: float, orders: int}> */
    public function daily(): Collection
    {
        $rows = $this->orders()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(total),0) as revenue')
            ->groupBy('d')
            ->pluck('revenue', 'd');

        $counts = $this->orders()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $out = collect();
        $cursor = $this->from->copy()->startOfDay();

        while ($cursor->lte($this->to)) {
            $key = $cursor->toDateString();

            $out->push([
                'label' => $cursor->translatedFormat('d M'),
                'revenue' => round((float) ($rows[$key] ?? 0), 2),
                'orders' => (int) ($counts[$key] ?? 0),
            ]);

            $cursor->addDay();
        }

        return $out;
    }

    /** @return Collection<int, object> */
    public function topProducts(int $limit = 10): Collection
    {
        return OrderItem::query()
            ->whereIn('order_id', $this->orders()->select('id'))
            ->selectRaw('name, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, object> */
    public function byZone(): Collection
    {
        return $this->orders()
            ->selectRaw('COALESCE(delivery_zone_name, ?) as zone, COUNT(*) as c, COALESCE(SUM(total),0) as revenue', ['Belirtilmemiş'])
            ->groupBy('zone')
            ->orderByDesc('revenue')
            ->get();
    }

    /** @return Collection<int, object> */
    public function byPaymentMethod(): Collection
    {
        return $this->orders()
            ->selectRaw('payment_method, COUNT(*) as c, COALESCE(SUM(total),0) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();
    }

    /** @return Collection<int, object> */
    public function byCoupon(): Collection
    {
        return $this->orders()
            ->whereNotNull('coupon_code')
            ->selectRaw('coupon_code, COUNT(*) as c, COALESCE(SUM(discount),0) as discount, COALESCE(SUM(total),0) as revenue')
            ->groupBy('coupon_code')
            ->orderByDesc('c')
            ->get();
    }

    /** @return Collection<int, object> */
    public function byCategory(): Collection
    {
        return DB::table('order_items')
            ->join('category_product', 'order_items.product_id', '=', 'category_product.product_id')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->whereIn('order_items.order_id', $this->orders()->select('id'))
            ->whereNull('categories.parent_id')
            ->selectRaw('categories.name as name, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get();
    }

    /** Yüzde değişim; önceki dönem sıfırsa null. */
    public static function change(float $now, float $before): ?float
    {
        if ($before <= 0) {
            return null;
        }

        return round((($now - $before) / $before) * 100, 1);
    }
}
