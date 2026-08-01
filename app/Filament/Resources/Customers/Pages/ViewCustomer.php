<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('İletişim')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('email')->label('E-posta')->copyable(),
                        TextEntry::make('phone')->label('Telefon')->copyable()->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Kayıt tarihi')
                            ->formatStateUsing(fn ($state) => $state?->translatedFormat('d F Y')),
                    ]),

                Section::make('Adres defteri')
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('addresses_view')
                            ->hiddenLabel()
                            ->state(function (User $record) {
                                $rows = $record->addresses()->with('zone')->get();

                                if ($rows->isEmpty()) {
                                    return new HtmlString('<p>Kayıtlı adres yok.</p>');
                                }

                                return new HtmlString($rows->map(fn ($a) => '<p style="margin-bottom:.6rem">'
                                    .'<strong>'.e($a->title).'</strong>'
                                    .($a->is_default ? ' · varsayılan' : '')
                                    .'<br>'.e($a->recipient_name)
                                    .($a->recipient_phone ? ' · '.e($a->recipient_phone) : '')
                                    .'<br><span style="opacity:.7">'.e($a->summary).'</span>'
                                    .'</p>')->implode(''));
                            }),
                    ]),

                Section::make('Siparişleri')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('orders_view')
                            ->hiddenLabel()
                            ->state(function (User $record) {
                                $orders = $record->orders()->limit(20)->get();

                                if ($orders->isEmpty()) {
                                    return new HtmlString('<p>Henüz sipariş yok.</p>');
                                }

                                $rows = $orders->map(function (Order $o) {
                                    $url = OrderResource::getUrl('edit', ['record' => $o]);

                                    return '<tr>'
                                        .'<td style="padding:.35rem 0"><a href="'.$url.'" style="text-decoration:underline"><strong>'.e($o->number).'</strong></a></td>'
                                        .'<td>'.$o->created_at?->translatedFormat('d M Y').'</td>'
                                        .'<td>'.e($o->status_label).'</td>'
                                        .'<td style="text-align:right">'.money($o->total).'</td>'
                                        .'</tr>';
                                })->implode('');

                                return new HtmlString(
                                    '<table style="width:100%;border-collapse:collapse;font-size:.92em">'.$rows.'</table>'
                                );
                            }),
                    ]),
            ]);
    }
}
