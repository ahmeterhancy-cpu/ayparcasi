<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gunun_teslimatlari')
                ->label('Günün teslimatları')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(route('print.day'))
                ->openUrlInNewTab(),

            CreateAction::make()
                ->label('Elle sipariş aç')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $count = fn (callable $modify) => $modify(Order::query())->count();

        return [
            'hepsi' => Tab::make('Hepsi'),

            'bugun' => Tab::make('Bugün teslim')
                ->modifyQueryUsing(fn ($q) => $q->whereDate('delivery_date', today()))
                ->badge($count(fn ($q) => $q->whereDate('delivery_date', today()))),

            'yeni' => Tab::make('Yeni')
                ->modifyQueryUsing(fn ($q) => $q->where('status', 'pending'))
                ->badge($count(fn ($q) => $q->where('status', 'pending')))
                ->badgeColor('warning'),

            'hazirlaniyor' => Tab::make('Hazırlanıyor')
                ->modifyQueryUsing(fn ($q) => $q->whereIn('status', ['confirmed', 'preparing'])),

            'yolda' => Tab::make('Yolda')
                ->modifyQueryUsing(fn ($q) => $q->where('status', 'on_the_way')),

            'teslim' => Tab::make('Teslim edildi')
                ->modifyQueryUsing(fn ($q) => $q->where('status', 'delivered')),

            'odenmemis' => Tab::make('Ödeme bekleyen')
                ->modifyQueryUsing(fn ($q) => $q->where('payment_status', 'unpaid')
                    ->whereNotIn('status', ['cancelled'])),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'hepsi';
    }
}
