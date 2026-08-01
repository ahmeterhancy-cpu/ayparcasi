<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductCsv;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
