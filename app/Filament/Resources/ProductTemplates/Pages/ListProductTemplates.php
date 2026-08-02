<?php

namespace App\Filament\Resources\ProductTemplates\Pages;

use App\Filament\Resources\ProductTemplates\ProductTemplateResource;
use App\Models\Category;
use App\Services\TemplateGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProductTemplates extends ListRecords
{
    protected static string $resource = ProductTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('katalogdan_olustur')
                ->label('Katalogdan oluştur')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->modalHeading('Mevcut ürünlerden şablon türet')
                ->modalDescription('Her alt kategori için bir şablon açılır: o kategorideki ürünlerin ortak açıklaması, bakım önerisi ve ek ürünleri alınır, fiyat grubun ortancası olur, boy seti grubu en iyi temsil eden üründen kopyalanır.')
                ->modalSubmitActionLabel('Şablonları oluştur')
                ->schema([
                    CheckboxList::make('exclude_parents')
                        ->label('Bu grupları atla')
                        ->options(fn () => Category::whereNull('parent_id')->orderBy('position')->pluck('name', 'id'))
                        ->columns(2)
                        ->helperText('Özel gün grupları ürün tipi değildir — "Sevgililer Günü" bir şablon değil, bir vesiledir. Onları atlamak iyi olur.'),

                    TextInput::make('min_products')
                        ->label('En az kaç ürün olsun')
                        ->numeric()
                        ->minValue(1)
                        ->default(2)
                        ->helperText('Bu sayının altındaki kategoriler için şablon açılmaz.'),

                    Toggle::make('refresh')
                        ->label('Aynı adlı şablonu güncelle')
                        ->helperText('Kapalıyken mevcut şablonlara dokunulmaz.'),
                ])
                ->action(function (array $data) {
                    $result = app(TemplateGenerator::class)->generate(
                        excludeParentIds: array_map('intval', $data['exclude_parents'] ?? []),
                        minProducts: (int) ($data['min_products'] ?? 2),
                        refresh: (bool) ($data['refresh'] ?? false),
                    );

                    $lines = [];

                    if ($result['created']) {
                        $lines[] = 'Açılan: '.implode(', ', $result['created']);
                    }

                    if ($result['updated']) {
                        $lines[] = 'Güncellenen: '.implode(', ', $result['updated']);
                    }

                    if ($result['skipped']) {
                        $lines[] = 'Atlanan: '.implode(', ', $result['skipped']);
                    }

                    $touched = count($result['created']) + count($result['updated']);

                    Notification::make()
                        ->title($touched > 0
                            ? $touched.' şablon hazır'
                            : 'Yeni şablon açılmadı')
                        ->body(implode(' · ', $lines) ?: 'Uygun kategori bulunamadı.')
                        ->success()
                        ->persistent()
                        ->send();
                }),

            CreateAction::make()->label('Şablon ekle'),
        ];
    }
}
