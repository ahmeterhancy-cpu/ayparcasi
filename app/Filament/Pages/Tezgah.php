<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Tezgâh modu — dükkânda telefonla ürün girmek için tek ekran.
 *
 * Panelin tam formu masaüstü içindir; burada yalnız üç alan var:
 * fotoğraf, ad, fiyat. Kaydedince form sıfırlanır ve aynı ekranda
 * kalırsınız, arka arkaya ürün girmek için.
 */
class Tezgah extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Tezgâh modu';

    protected static ?string $title = 'Tezgâh modu';

    protected string $view = 'filament.pages.tezgah';

    /** @var array<string, mixed> */
    public array $data = [];

    /** Bu oturumda eklenenler — sayfa yenilenince sıfırlanır. */
    public array $eklenenler = [];

    public function mount(): void
    {
        $this->form->fill($this->defaults());
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'template_id' => ProductTemplate::active()->orderBy('position')->value('id'),
            'is_active' => true,
            'stock' => 1,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Fotoğraf')
                            ->image()
                            ->imageEditor()
                            ->directory('products')
                            ->disk('public')
                            ->maxSize(6144)
                            ->required()
                            ->helperText('Telefonda dokununca kamera açılır.'),

                        TextInput::make('name')
                            ->label('Ürün adı')
                            ->placeholder('Kırmızı gül buketi')
                            ->required()
                            ->maxLength(190),

                        TextInput::make('price')
                            ->label('Fiyat')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->suffix('TL'),

                        TextInput::make('stock')
                            ->label('Stok adedi')
                            ->numeric()
                            ->minValue(0)
                            ->default(1),

                        Select::make('template_id')
                            ->label('Şablon')
                            ->placeholder('Şablonsuz')
                            ->options(fn () => ProductTemplate::active()->orderBy('position')->pluck('name', 'id'))
                            ->visible(fn () => ProductTemplate::active()->exists())
                            ->helperText('Açıklama, kategori, ek ürünler ve boylar şablondan gelir.'),

                        Select::make('category_ids')
                            ->label('Kategoriler')
                            ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Şablon seçtiyseniz boş bırakabilirsiniz.'),

                        Toggle::make('is_active')
                            ->label('Hemen yayınla')
                            ->default(true)
                            ->helperText('Kapatırsanız taslak olarak kalır.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $template = ProductTemplate::find($data['template_id'] ?? null);

        $image = $data['hero_image'] ?? null;
        $image = is_array($image) ? (reset($image) ?: null) : $image;

        $product = Product::create([
            'name' => $data['name'],
            'slug' => Product::uniqueSlug($data['name']),
            'price' => (float) $data['price'],
            'hero_image' => $image,
            'stock' => (int) ($data['stock'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'position' => (int) Product::max('position') + 1,
        ]);

        $template?->applyTo($product);

        // Şablonun fiyatı ve stoğu buradaki elle girilen değeri ezmesin.
        $product->forceFill([
            'price' => (float) $data['price'],
            'stock' => (int) ($data['stock'] ?? 0),
        ])->save();

        if (filled($data['category_ids'] ?? [])) {
            $product->categories()->syncWithoutDetaching($data['category_ids']);
        }

        array_unshift($this->eklenenler, [
            'id' => $product->id,
            'name' => $product->name,
            'price' => money($product->display_price),
            'image' => img_url($product->hero_image),
            'active' => $product->is_active,
            'url' => ProductResource::getUrl('edit', ['record' => $product]),
        ]);

        Notification::make()
            ->title($product->name.' eklendi')
            ->body($product->is_active ? 'Vitrinde yayında.' : 'Taslak olarak kaydedildi.')
            ->success()
            ->send();

        // Sıradaki ürün için temiz form, ayarlar hatırlansın
        $this->form->fill([
            ...$this->defaults(),
            'template_id' => $data['template_id'] ?? null,
            'category_ids' => $data['category_ids'] ?? [],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }
}
