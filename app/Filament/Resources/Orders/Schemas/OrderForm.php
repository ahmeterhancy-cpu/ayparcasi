<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->description('Ürün seçtiğinizde fiyat otomatik gelir; pazarlık yaptıysanız elle değiştirebilirsiniz. Toplamlar kaydettiğinizde hesaplanır.')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->addActionLabel('Ürün ekle')
                            ->reorderable(false)
                            ->columns(12)
                            ->minItems(1)
                            ->itemLabel(fn (array $state) => $state['name'] ?? null)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Ürün')
                                    ->columnSpan(5)
                                    ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $product = Product::with('variants')->find($state);

                                        if (! $product) {
                                            return;
                                        }

                                        $set('name', $product->name);
                                        $set('image', $product->hero_image);
                                        $set('variant_name', null);
                                        $set('unit_price', $product->effective_price);
                                    }),

                                Select::make('variant_name')
                                    ->label('Boy')
                                    ->columnSpan(3)
                                    ->options(fn (Get $get) => Product::find($get('product_id'))
                                        ?->variants()
                                        ->where('is_active', true)
                                        ->orderBy('position')
                                        ->pluck('name', 'name') ?? [])
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $product = Product::with('variants')->find($get('product_id'));
                                        $variant = $product?->variants->firstWhere('name', $state);

                                        if ($variant) {
                                            $set('unit_price', $variant->effectivePriceFor($product));
                                        }
                                    })
                                    ->placeholder('—'),

                                TextInput::make('quantity')
                                    ->label('Adet')
                                    ->columnSpan(2)
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('unit_price')
                                    ->label('Birim fiyat')
                                    ->columnSpan(2)
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->suffix('TL'),

                                Hidden::make('name')->default(''),
                                Hidden::make('image'),
                                Hidden::make('line_total')->default(0),
                            ]),

                        Placeholder::make('totals_view')
                            ->label('Tutarlar')
                            ->content(function (?Order $record) {
                                if (! $record) {
                                    return 'Kaydettiğinizde hesaplanır.';
                                }

                                $rows = '<tr><td>Ara toplam</td><td style="text-align:right">'.money($record->subtotal).'</td></tr>';

                                if ((float) $record->discount > 0) {
                                    $rows .= '<tr><td>İndirim'.($record->coupon_code ? ' ('.e($record->coupon_code).')' : '')
                                        .'</td><td style="text-align:right">-'.money($record->discount).'</td></tr>';
                                }

                                $rows .= '<tr><td>Teslimat</td><td style="text-align:right">'
                                    .((float) $record->delivery_fee > 0 ? money($record->delivery_fee) : 'Ücretsiz').'</td></tr>'
                                    .'<tr style="font-weight:700;font-size:1.1em"><td style="padding-top:.4rem">Toplam</td>'
                                    .'<td style="text-align:right;padding-top:.4rem">'.money($record->total).'</td></tr>';

                                if ((float) $record->refunded_total > 0) {
                                    $rows .= '<tr style="color:#b03826"><td>İade edilen</td>'
                                        .'<td style="text-align:right">-'.money($record->refunded_total).'</td></tr>';
                                }

                                return new HtmlString('<table style="width:100%;max-width:22rem;border-collapse:collapse;font-size:.95em">'.$rows.'</table>');
                            }),
                    ]),

                Section::make('Tutar ayarları')
                    ->columnSpanFull()
                    ->columns(3)
                    ->collapsed()
                    ->description('Elle sipariş açarken teslimat ücretini ve varsa indirimi buradan girin.')
                    ->schema([
                        TextInput::make('delivery_fee')
                            ->label('Teslimat ücreti')
                            ->numeric()->minValue(0)->default(0)->suffix('TL'),

                        TextInput::make('discount')
                            ->label('İndirim')
                            ->numeric()->minValue(0)->default(0)->suffix('TL'),

                        TextInput::make('coupon_code')
                            ->label('Kupon kodu (bilgi)')
                            ->maxLength(60),
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
