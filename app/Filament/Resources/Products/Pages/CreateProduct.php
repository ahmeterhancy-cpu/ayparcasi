<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** Formda seçilen şablon — boy seti kayıttan sonra kopyalanır. */
    private ?ProductTemplate $template = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // "sablon" bir ürün sütunu değil, yalnız formu doldurmak için var.
        $this->template = ProductTemplate::find($data['sablon'] ?? null);

        unset($data['sablon']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Metin, kategori ve ek ürünler formda zaten dolduruldu; boylar
        // ancak ürün kaydedildikten sonra eklenebiliyor.
        $this->template?->applyVariants($this->record);
    }
}
