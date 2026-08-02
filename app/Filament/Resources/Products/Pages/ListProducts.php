<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductTemplate;
use App\Services\BulkPhotoDrafts;
use App\Services\ProductCsv;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toplu_fotograf')
                ->label('Toplu fotoğraf')
                ->icon('heroicon-o-camera')
                ->color('gray')
                ->modalHeading('Fotoğraflardan taslak ürün aç')
                ->modalDescription('Bir çekimden çıkan fotoğrafları birden sürükleyin. Her fotoğraf için yayından kaldırılmış bir taslak ürün açılır; adı ve fiyatı listede, form açmadan doldurabilirsiniz.')
                ->modalSubmitActionLabel('Taslakları aç')
                ->schema([
                    FileUpload::make('images')
                        ->label('Fotoğraflar')
                        ->image()
                        ->multiple()
                        ->required()
                        ->storeFiles(false)
                        ->maxSize(6144)
                        ->maxFiles(30)
                        ->helperText('En fazla 30 fotoğraf, her biri 6 MB’a kadar.'),

                    Select::make('template_id')
                        ->label('Şablon')
                        ->placeholder('Şablonsuz — yalnız fotoğraf')
                        ->options(fn () => ProductTemplate::active()->orderBy('position')->pluck('name', 'id'))
                        ->visible(fn () => ProductTemplate::active()->exists())
                        ->helperText('Seçerseniz açıklama, kategori, ek ürünler ve boylar da hazır gelir.'),

                    Placeholder::make('sablon_yok')
                        ->label('Şablon')
                        ->visible(fn () => ! ProductTemplate::active()->exists())
                        ->content('Henüz şablon yok. Bir ürünü açıp "Şablon çıkar" derseniz, sonraki toplu yüklemelerde açıklama ve kategoriler de hazır gelir.'),

                    Toggle::make('name_from_filename')
                        ->label('Ürün adını dosya adından al')
                        ->default(true)
                        ->helperText('IMG_1234 gibi anlamsız adlar atlanır, o ürünler "Yeni ürün 1" olarak açılır.'),
                ])
                ->action(function (array $data) {
                    $result = app(BulkPhotoDrafts::class)->create(
                        files: (array) $data['images'],
                        template: ProductTemplate::find($data['template_id'] ?? null),
                        nameFromFilename: (bool) ($data['name_from_filename'] ?? true),
                    );

                    Notification::make()
                        ->title($result['created'].' taslak ürün açıldı')
                        ->body('Listede adı ve fiyatı doldurup "Satışta" düğmesiyle yayınlayabilirsiniz.')
                        ->success()
                        ->send();
                }),

            ActionGroup::make([
                Action::make('disa_aktar')
                    ->label('CSV olarak indir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => app(ProductCsv::class)->export()),

                Action::make('ice_aktar')
                    ->label('CSV yükle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Ürünleri CSV ile güncelle')
                    ->modalDescription('En güvenli yol: önce mevcut ürünleri indirin, o dosyayı düzenleyip geri yükleyin. Bağlantı adresi (ya da stok kodu) eşleşen ürün güncellenir, eşleşmeyen yeni ürün olarak eklenir.')
                    ->modalSubmitActionLabel('Yükle ve işle')
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV dosyası')
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->helperText('Ayraç noktalı virgül (;) olmalı — Excel Türkçe ayarında zaten böyle kaydeder.'),
                    ])
                    ->action(function (array $data) {
                        $file = $data['file'];
                        $file = is_array($file) ? reset($file) : $file;

                        $result = app(ProductCsv::class)->import($file->getRealPath());

                        $body = "{$result['created']} yeni ürün, {$result['updated']} güncelleme";

                        if ($result['skipped'] > 0) {
                            $body .= ", {$result['skipped']} satır atlandı";
                        }

                        $notification = Notification::make()->title('İçe aktarma tamamlandı');

                        if ($result['errors']) {
                            $notification
                                ->warning()
                                ->persistent()
                                ->body($body.' — '.implode(' / ', array_slice($result['errors'], 0, 5))
                                    .(count($result['errors']) > 5 ? ' …' : ''));
                        } else {
                            $notification->success()->body($body);
                        }

                        $notification->send();
                    }),
            ])
                ->label('CSV')
                ->icon('heroicon-o-table-cells')
                ->button()
                ->color('gray'),

            CreateAction::make()->label('Ürün ekle'),
        ];
    }
}
