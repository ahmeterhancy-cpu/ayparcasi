<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Services\SalesReport;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Reports extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Raporlar';

    protected static ?string $title = 'Raporlar';

    protected string $view = 'filament.pages.reports';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'preset' => 'bu_ay',
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(3)
            ->components([
                Select::make('preset')
                    ->label('Dönem')
                    ->options([
                        'bugun' => 'Bugün',
                        'dun' => 'Dün',
                        'bu_hafta' => 'Bu hafta',
                        'bu_ay' => 'Bu ay',
                        'gecen_ay' => 'Geçen ay',
                        'son_90' => 'Son 90 gün',
                        'bu_yil' => 'Bu yıl',
                        'ozel' => 'Özel aralık',
                    ])
                    ->default('bu_ay')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        [$from, $to] = self::presetRange($state);

                        if ($from) {
                            $set('from', $from->toDateString());
                            $set('to', $to->toDateString());
                        }
                    }),

                DatePicker::make('from')
                    ->label('Başlangıç')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->live()
                    ->disabled(fn (Get $get) => $get('preset') !== 'ozel'),

                DatePicker::make('to')
                    ->label('Bitiş')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->live()
                    ->disabled(fn (Get $get) => $get('preset') !== 'ozel'),
            ]);
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    public static function presetRange(string $preset): array
    {
        return match ($preset) {
            'bugun' => [now()->startOfDay(), now()->endOfDay()],
            'dun' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'bu_hafta' => [now()->startOfWeek(), now()->endOfDay()],
            'bu_ay' => [now()->startOfMonth(), now()->endOfDay()],
            'gecen_ay' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'son_90' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'bu_yil' => [now()->startOfYear(), now()->endOfDay()],
            default => [null, null],
        };
    }

    public function report(): SalesReport
    {
        $from = Carbon::parse($this->data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($this->data['to'] ?? now())->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return new SalesReport($from, $to);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('csv')
                ->label('Siparişleri CSV indir')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    private function exportCsv(): StreamedResponse
    {
        $report = $this->report();
        $filename = 'siparisler-'.$report->from->format('Y-m-d').'_'.$report->to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel için UTF-8 imzası

            fputcsv($out, [
                'Sipariş No', 'Tarih', 'Durum', 'Ödeme durumu', 'Ödeme yöntemi',
                'Müşteri', 'Telefon', 'E-posta',
                'Alıcı', 'Bölge', 'Teslim tarihi', 'Saat aralığı',
                'Ara toplam', 'İndirim', 'Kupon', 'Teslimat', 'Toplam', 'İade',
            ], ';');

            Order::query()
                ->whereBetween('created_at', [$report->from, $report->to])
                ->orderBy('created_at')
                ->chunk(300, function ($orders) use ($out) {
                    foreach ($orders as $o) {
                        fputcsv($out, [
                            $o->number,
                            $o->created_at?->format('d.m.Y H:i'),
                            $o->status_label,
                            Order::PAYMENT_STATUSES[$o->payment_status] ?? $o->payment_status,
                            $o->payment_method_label,
                            $o->customer_name,
                            $o->customer_phone,
                            $o->customer_email,
                            $o->recipient_name,
                            $o->delivery_zone_name,
                            $o->delivery_date?->format('d.m.Y'),
                            $o->delivery_slot,
                            number_format((float) $o->subtotal, 2, ',', ''),
                            number_format((float) $o->discount, 2, ',', ''),
                            $o->coupon_code,
                            number_format((float) $o->delivery_fee, 2, ',', ''),
                            number_format((float) $o->total, 2, ',', ''),
                            number_format((float) $o->refunded_total, 2, ',', ''),
                        ], ';');
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
