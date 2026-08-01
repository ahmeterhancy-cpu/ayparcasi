<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockInquiry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $todayDeliveries = Order::whereDate('delivery_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $pending = Order::where('status', 'pending')->count();

        $monthRevenue = (float) Order::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        // Son 7 günün günlük sipariş sayısı — mini grafik için
        $chart = collect(range(6, 0))
            ->map(fn ($d) => Order::whereDate('created_at', today()->subDays($d))->count())
            ->all();

        $inquiries = StockInquiry::where('handled', false)->count();

        $outOfStock = Product::query()
            ->where('is_active', true)
            ->where('track_stock', true)
            ->where('stock', '<=', 0)
            ->whereDoesntHave('variants')
            ->count();

        return [
            Stat::make('Bugün teslim edilecek', $todayDeliveries)
                ->description($pending > 0 ? $pending.' sipariş onay bekliyor' : 'Bekleyen sipariş yok')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success'),

            Stat::make('Bu ayki ciro', money($monthRevenue))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($chart)
                ->color('primary'),

            Stat::make('Cevap bekleyen stok sorusu', $inquiries)
                ->description($outOfStock > 0 ? $outOfStock.' ürünün stoğu bitti' : 'Stoklar yolunda')
                ->descriptionIcon($outOfStock > 0 ? 'heroicon-m-archive-box-x-mark' : 'heroicon-m-archive-box')
                ->color($inquiries > 0 || $outOfStock > 0 ? 'danger' : 'success'),
        ];
    }
}
