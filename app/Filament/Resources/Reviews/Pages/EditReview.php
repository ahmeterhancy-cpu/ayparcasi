<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** Cevap yazıldığı an tarihi damgalanır. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['replied_at'] = filled($data['reply'] ?? null) ? now() : null;

        return $data;
    }
}
