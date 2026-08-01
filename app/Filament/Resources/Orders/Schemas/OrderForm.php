<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Durum')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('number_view')
                            ->label('Sipariş no')
                            ->content(fn (?Order $record) => $record?->number ?? '—'),

                        Select::make('status')
                            ->label('Sipariş durumu')
                            ->options(Order::STATUSES)
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Select::make('payment_status')
                            ->label('Ödeme durumu')
                            ->options(Order::PAYMENT_STATUSES)
                            ->default('unpaid')
                            ->required()
                            ->native(false),

                        Select::make('payment_method')
                            ->label('Ödeme yöntemi')
                            ->options(Order::PAYMENT_METHODS)
                            ->required()
                            ->native(false),

                        Placeholder::make('created_view')
                            ->label('Oluşturuldu')
                            ->content(fn (?Order $record) => $record?->created_at?->translatedFormat('d F Y, H:i') ?? '—'),

                        Placeholder::make('paid_view')
                            ->label('Ödeme zamanı')
                            ->content(fn (?Order $record) => $record?->paid_at?->translatedFormat('d F Y, H:i') ?? 'Ödenmedi'),
                    ]),

                Section::make('Sipariş kalemleri')
                    ->columnSpan(2)
                    ->schema([
                        Placeholder::make('items_view')
                            ->hiddenLabel()
                            ->content(function (?Order $record) {
                                if (! $record) {
                                    return new HtmlString('<p class="text-sm">Sipariş kaydedildikten sonra görünür.</p>');
                                }

                                $rows = $record->items->map(function ($item) {
                                    $addons = $item->addons
                                        ? '<div style="opacity:.65;font-size:.85em">+ '.e(collect($item->addons)->pluck('name')->implode(', ')).'</div>'
                                        : '';

                                    return '<tr>'
                                        .'<td style="padding:.5rem 0">'
                                        .'<strong>'.e($item->name).'</strong>'
                                        .($item->variant_name ? ' <span style="opacity:.65">('.e($item->variant_name).')</span>' : '')
                                        .$addons
                                        .'</td>'
                                        .'<td style="text-align:center;white-space:nowrap">'.$item->quantity.' adet</td>'
                                        .'<td style="text-align:right;white-space:nowrap">'.money($item->line_total).'</td>'
                                        .'</tr>';
                                })->implode('');

                                $totals = '<tr><td colspan="2" style="padding-top:.75rem">Ara toplam</td>'
                                    .'<td style="text-align:right;padding-top:.75rem">'.money($record->subtotal).'</td></tr>';

                                if ((float) $record->discount > 0) {
                                    $totals .= '<tr><td colspan="2">İndirim'
                                        .($record->coupon_code ? ' ('.e($record->coupon_code).')' : '')
                                        .'</td><td style="text-align:right">-'.money($record->discount).'</td></tr>';
                                }

                                $totals .= '<tr><td colspan="2">Teslimat</td><td style="text-align:right">'
                                    .((float) $record->delivery_fee > 0 ? money($record->delivery_fee) : 'Ücretsiz').'</td></tr>'
                                    .'<tr style="font-weight:700;font-size:1.1em"><td colspan="2" style="padding-top:.5rem">Toplam</td>'
                                    .'<td style="text-align:right;padding-top:.5rem">'.money($record->total).'</td></tr>';

                                return new HtmlString(
                                    '<table style="width:100%;border-collapse:collapse;font-size:.92em">'
                                    .$rows
                                    .'<tr><td colspan="3"><hr style="border:0;border-top:1px solid currentColor;opacity:.15;margin:.5rem 0"></td></tr>'
                                    .$totals
                                    .'</table>'
                                );
                            }),
                    ]),

                Section::make('Sipariş veren')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('account_view')
                            ->label('Hesap')
                            ->content(fn (?Order $record) => $record?->user
                                ? new HtmlString('<a href="'.CustomerResource::getUrl('view', ['record' => $record->user]).'" style="text-decoration:underline">'.e($record->user->name).'</a>')
                                : 'Misafir sipariş'),

                        TextInput::make('customer_name')->label('Ad soyad')->required(),
                        TextInput::make('customer_phone')->label('Telefon')->required()->tel(),
                        TextInput::make('customer_email')->label('E-posta')->email(),
                    ]),

                Section::make('Teslimat')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('recipient_name')->label('Alıcı')->required(),
                        TextInput::make('recipient_phone')->label('Alıcı telefonu')->tel(),

                        Select::make('delivery_zone_id')
                            ->label('Bölge')
                            ->relationship('zone', 'name')
                            ->native(false),

                        DatePicker::make('delivery_date')
                            ->label('Teslim tarihi')
                            ->native(false)
                            ->displayFormat('d.m.Y'),

                        TextInput::make('delivery_slot')->label('Saat aralığı'),

                        TextInput::make('delivery_zone_name')
                            ->label('Bölge adı (sipariş anındaki)')
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('delivery_address')
                            ->label('Adres')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Kart notu ve notlar')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Textarea::make('card_message')->label('Kart notu')->rows(3),
                        Textarea::make('note')->label('Müşteri notu')->rows(3),
                        TextInput::make('card_sender')->label('Kartta gönderen adı'),
                        Checkbox::make('hide_sender')->label('Gönderen adı gizli'),
                    ]),
            ]);
    }
}
