<?php

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disa_aktar')
                ->label('CSV olarak indir')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    private function exportCsv(): StreamedResponse
    {
        $filename = 'bulten-aboneleri-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // Excel'in UTF-8'i doğru okuması için BOM
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['E-posta', 'Ad', 'Aktif', 'Kayıt tarihi'], ';');

            NewsletterSubscriber::orderBy('created_at')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->email,
                        $row->name,
                        $row->is_active ? 'Evet' : 'Hayır',
                        $row->created_at?->format('d.m.Y H:i'),
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
