<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ProductTemplates\ProductTemplateResource;
use App\Models\ProductTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sablon_cikar')
                ->label('Şablon çıkar')
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->modalHeading('Bu üründen şablon çıkar')
                ->modalDescription('Açıklama, içindekiler, bakım önerisi, rozet, kategoriler, ek ürünler ve boy seçenekleri şablona kopyalanır. Ürünün kendisine dokunulmaz.')
                ->modalSubmitActionLabel('Şablonu oluştur')
                ->schema([
                    TextInput::make('name')
                        ->label('Şablon adı')
                        ->required()
                        ->maxLength(80)
                        ->default(fn () => $this->record->categories->first()?->name ?? $this->record->name)
                        ->helperText('Ürün tipini anlatan kısa bir ad: "Buket", "Orkide", "Aranjman".'),
                ])
                ->action(function (array $data) {
                    $this->record->loadMissing('categories', 'addons', 'variants');

                    $template = ProductTemplate::fromProduct($this->record, $data['name']);

                    Notification::make()
                        ->title($template->name.' şablonu oluşturuldu')
                        ->body('Yeni ürün açarken, toplu fotoğrafta ve tezgâh modunda seçilebilir.')
                        ->success()
                        ->actions([
                            Action::make('duzenle')
                                ->label('Şablonu düzenle')
                                ->url(ProductTemplateResource::getUrl('edit', ['record' => $template])),
                        ])
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
