<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Addon;
use App\Models\ProductTemplate;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        /*
         * İki ray: solda uzun içerik (metin, fiyat, görsel), sağda kısa
         * kartlar (yayın, stok, ek ürün). Bölümler tek tek columnSpan
         * aldığında kartların altları hizasız kalıyor, aralarında boşluk
         * açılıyor ve sayfa dağınık görünüyordu; ray olarak gruplanınca
         * her iki sütun da kesintisiz akıyor.
         */
        return $schema
            ->columns(3)
            ->components([

                // Yalnız yeni üründe: seçilen şablon alanları anında doldurur.
                // Boy seti kayıttan sonra kopyalanır (bkz. CreateProduct).
                Section::make('Şablondan başla')
                    ->columnSpanFull()
                    ->visible(fn (string $operation) => $operation === 'create')
                    ->schema([
                        // Şablon yokken alanı gizlemek "böyle bir şey yok" gibi
                        // okunuyordu; ne işe yaradığını burada söylüyoruz.
                        Placeholder::make('sablon_yok')
                            ->hiddenLabel()
                            ->visible(fn () => ! ProductTemplate::active()->exists())
                            ->content(new HtmlString(
                                'Henüz şablon yok. Sık eklediğiniz ürün tipleri için bir şablon açarsanız '
                                .'açıklama, bakım önerisi, kategori, ek ürünler ve boy seti burada hazır gelir. '
                                .'En kısa yolu: beğendiğiniz bir ürünü açıp <strong>Şablon çıkar</strong> deyin.'
                            )),

                        Select::make('sablon')
                            ->hiddenLabel()
                            ->placeholder('Şablon seçin — alanlar hazır gelsin')
                            ->visible(fn () => ProductTemplate::active()->exists())
                            ->options(fn () => ProductTemplate::active()
                                ->orderBy('position')
                                ->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $template = ProductTemplate::find($state);

                                if (! $template) {
                                    return;
                                }

                                foreach ($template->fields() as $field => $value) {
                                    if ($value !== null) {
                                        $set($field, $value);
                                    }
                                }

                                $set('categories', $template->categoryIds());
                                $set('addons', $template->addonIds());
                            })
                            ->helperText('Şablonları Katalog → Ürün şablonları bölümünden yönetirsiniz.'),
                    ]),

                // --- Sol ray: içerik --------------------------------------
                Group::make()->columnSpan(2)->schema([

                    Section::make('Ürün bilgileri')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Ürün adı')
                                ->required()
                                ->maxLength(190)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $set, $context) {
                                    if ($context === 'create') {
                                        $set('slug', Str::slug((string) $state));
                                    }
                                })
                                ->columnSpanFull(),

                            TextInput::make('slug')
                                ->label('Bağlantı adresi')
                                ->helperText('Boş bırakırsanız ürün adından üretilir.')
                                ->maxLength(190)
                                ->unique(ignoreRecord: true),

                            TextInput::make('sku')
                                ->label('Stok kodu')
                                ->maxLength(60),

                            Textarea::make('short_description')
                                ->label('Kısa açıklama')
                                ->helperText('Ürün başlığının hemen altında görünür. 1–2 cümle.')
                                ->rows(2)
                                ->maxLength(300)
                                ->columnSpanFull(),

                            Textarea::make('description')
                                ->label('Açıklama')
                                ->rows(6)
                                ->columnSpanFull(),

                            Textarea::make('contents')
                                ->label('İçindekiler')
                                ->helperText('Her satıra bir madde yazın.')
                                ->rows(4),

                            Textarea::make('care_notes')
                                ->label('Bakım önerisi')
                                ->helperText('Her satıra bir madde yazın.')
                                ->rows(4),
                        ]),

                    Section::make('Fiyat')
                        ->columns(2)
                        ->description('Boy seçenekleri eklerseniz fiyat oradan alınır; buradaki fiyat yalnızca boy seçeneği olmayan ürünlerde kullanılır.')
                        ->schema([
                            TextInput::make('price')
                                ->label('Fiyat')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(0)
                                ->suffix('TL'),

                            TextInput::make('compare_at_price')
                                ->label('Üstü çizili fiyat')
                                ->helperText('İndirim göstermek için. Fiyattan büyük olmalı.')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('TL'),

                            DateTimePicker::make('sale_starts_at')
                                ->label('İndirim başlangıcı')
                                ->native(false)
                                ->displayFormat('d.m.Y H:i')
                                ->helperText('Boş bırakırsanız indirim hemen geçerlidir.'),

                            DateTimePicker::make('sale_ends_at')
                                ->label('İndirim bitişi')
                                ->native(false)
                                ->displayFormat('d.m.Y H:i')
                                ->after('sale_starts_at')
                                ->helperText('Bu tarihten sonra ürün üstü çizili fiyattan satılır.'),
                        ]),

                    Section::make('Görseller')
                        ->columns(2)
                        ->schema([
                            FileUpload::make('hero_image')
                                ->label('Kapak görseli')
                                ->image()
                                ->imageEditor()
                                ->directory('products')
                                ->disk('public')
                                ->maxSize(6144),

                            FileUpload::make('gallery')
                                ->label('Galeri')
                                ->helperText('İlk galeri görseli, listede fareyle üzerine gelince gösterilir.')
                                ->image()
                                ->multiple()
                                ->reorderable()
                                ->directory('products')
                                ->disk('public')
                                ->maxSize(6144)
                                ->maxFiles(8),
                        ]),

                    Section::make('Arama motoru')
                        ->columns(2)
                        ->collapsed()
                        ->schema([
                            TextInput::make('meta_title')
                                ->label('Meta başlık')
                                ->maxLength(190),

                            Textarea::make('meta_description')
                                ->label('Meta açıklama')
                                ->rows(2)
                                ->maxLength(300),
                        ]),
                ]),

                // --- Sağ ray: kısa kartlar --------------------------------
                Group::make()->columnSpan(1)->schema([

                    Section::make('Yayın')
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Satışta')
                                ->default(true),

                            Toggle::make('is_featured')
                                ->label('Ana sayfada öne çıkar')
                                ->helperText('"Gözden kaçmayanlar" bölümünde görünür.'),

                            Toggle::make('same_day')
                                ->label('Aynı gün teslime uygun')
                                ->default(true),

                            TextInput::make('badge')
                                ->label('Rozet')
                                ->placeholder('Çok satan')
                                ->maxLength(40),

                            TextInput::make('position')
                                ->label('Sıra')
                                ->numeric()
                                ->default(0)
                                ->helperText('Küçük sayı önce görünür.'),

                            Select::make('categories')
                                ->label('Kategoriler')
                                ->relationship('categories', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->required(),
                        ]),

                    Section::make('Stok')
                        ->schema([
                            Toggle::make('track_stock')
                                ->label('Stok takibi yapılsın')
                                ->helperText('Kapatırsanız durumu elle seçersiniz.')
                                ->default(true)
                                ->live(),

                            TextInput::make('stock')
                                ->label('Stok adedi')
                                ->numeric()
                                ->default(0)
                                ->visible(fn (Get $get) => (bool) $get('track_stock'))
                                ->helperText('Boy seçeneği olan ürünlerde adet boy başına tutulur.'),

                            Select::make('stock_status')
                                ->label('Stok durumu')
                                ->options([
                                    'in_stock' => 'Stokta',
                                    'low' => 'Son birkaç adet',
                                    'made_to_order' => 'Siparişe özel hazırlanır',
                                    'out_of_stock' => 'Tükendi',
                                ])
                                ->default('in_stock')
                                ->visible(fn (Get $get) => ! $get('track_stock')),
                        ]),

                    Section::make('Yanına ekleyin')
                        ->description('Sepete atarken önerilecek ek ürünler. Hiçbirini seçmezseniz ürün sayfasında bu bölüm görünmez.')
                        ->schema([
                            CheckboxList::make('addons')
                                ->hiddenLabel()
                                ->relationship('addons', 'name')
                                // Panelde Tailwind sınıfları çalışmıyor (bkz.
                                // filament/theme), o yüzden satır içi stil.
                                ->allowHtml()
                                ->getOptionLabelFromRecordUsing(function (Addon $record) {
                                    $image = img_url($record->image);

                                    $thumb = $image
                                        ? '<img src="'.e($image).'" alt="" loading="lazy" style="'
                                            .'width:2.25rem;height:2.25rem;flex:none;border-radius:6px;object-fit:cover">'
                                        : '<span aria-hidden="true" style="width:2.25rem;height:2.25rem;flex:none;'
                                            .'border-radius:6px;background:var(--ay-sand,#efe4d3)"></span>';

                                    return new HtmlString(
                                        '<span style="display:inline-flex;align-items:center;gap:.6rem">'
                                        .$thumb
                                        .'<span>'.e($record->name).' — '.e(money($record->price))
                                        .($record->is_active ? '' : ' <em style="opacity:.65">(pasif)</em>')
                                        .'</span></span>'
                                    );
                                })
                                ->helperText('Listeyi Katalog → Ek ürünler bölümünden yönetirsiniz.'),
                        ]),
                ]),
            ]);
    }
}
