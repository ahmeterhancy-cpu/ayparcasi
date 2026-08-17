<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            // Sütunu elle gizleyip gösterme kararı yenilemeden sonra da dursun.
            ->persistColumnsInSession()
            ->columns([
                ImageColumn::make('hero_image')
                    ->label('')
                    ->disk('public')
                    ->imageSize(48),

                // Ad ve fiyat satır içinde düzenlenebilir: toplu fotoğraftan
                // açılan taslakları form açmadan doldurmak için.
                // Emici sütun artık bu DEĞİL — artan genişliği kategori alıyor.
                // Genişliği burada `width()` ile vermek İŞE YARAMAZ: emici sütun
                // width:100% olduğu için diğerlerini min-content'e doğru
                // sıkıştırıyor ve adlar kırpılıyordu. Tutan tek şey min-width,
                // o taban marka katmanında (theme.blade.php, 16rem).
                TextInputColumn::make('name')
                    ->label('Ürün')
                    ->searchable()
                    ->sortable()
                    ->rules(['required', 'max:190'])
                    ->beforeStateUpdated(function ($record, $state) {
                        // Taslağın adı değişince bağlantı adresi de düzelsin.
                        // Yayına girmiş üründe adres sabit kalır — paylaşılmış
                        // bağlantılar ve arama motoru sonuçları kırılmasın.
                        if (! $record->is_active) {
                            $record->slug = Product::uniqueSlug((string) $state, $record->id);
                        }
                    }),

                // Kategorilerin HEPSİ görünür — liste kısıtlaması yok. Bir ürün
                // 10'dan fazla kategoriye bağlı olabiliyor; taşmayı `wrap()`
                // önlüyor (kaba `flex-wrap` ekleyip rozetleri alt satıra sarar).
                //
                // Artan genişliği BU sütun emiyor (`grow()`). Sabit genişlikli
                // sütunların toplamı tabloyu doldurmadığında tarayıcı boşluğu
                // hücrelere dağıtıyor; emici belirtilmezse boşluk fiyat/rozet
                // gibi küçük alanlara gidip onları şişiriyor. 16rem sabitken
                // rozetler tek tek alt satıra iniyor ve satırlar uzuyordu —
                // yer açmak sarma sayısını, dolayısıyla satır yüksekliğini
                // düşürüyor. `grow()` yalnız `width()` boşken devreye girer,
                // o yüzden sabit genişlik kaldırıldı.
                TextColumn::make('categories.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->wrap()
                    ->grow()
                    ->toggleable(),

                TextInputColumn::make('price')
                    ->label('Fiyat')
                    ->sortable()
                    ->type('number')
                    ->rules(['numeric', 'min:0'])
                    // Filament satır içi girdilere sabit `min-width: 12rem`
                    // veriyor; o taban marka katmanında kaldırılıyor (bkz.
                    // theme.blade.php), yoksa buradaki genişlik hiç tutmuyor.
                    // 8rem = "2400.00" + artır/azalt oku + iç boşluklar.
                    ->width('8rem')
                    // Boy seçeneği olan üründe fiyat boylardan gelir
                    ->disabled(fn ($record) => (bool) $record?->has_variants),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->state(fn ($record) => $record?->stock_label)
                    ->color(fn ($record) => match ($record?->stock_state) {
                        'out_of_stock' => 'danger',
                        'low' => 'warning',
                        'made_to_order' => 'info',
                        default => 'success',
                    }),

                ToggleColumn::make('is_active')
                    ->label('Satışta'),

                // Bu ikisi varsayılan GÖRÜNÜR. Önceden gizliydi; sütun
                // yöneticisinden açılsa da tablo `persistColumnsInSession()`
                // kullanmadığı için her yenilemede geri kayboluyordu.
                //
                // İkisi de satır içinde düzenlenir — vitrinde en sık oynanan
                // alanlar bunlar, ürün formunu açmaya değmiyor.
                ToggleColumn::make('is_featured')
                    ->label('Öne çıkan')
                    ->toggleable(),

                // Rozet formda da serbest metin (ProductForm'da TextInput,
                // en fazla 40 karakter) — burada da öyle kalsın ki panelden
                // yeni bir rozet adı yazılabilsin. Boş bırakmak rozeti kaldırır.
                TextInputColumn::make('badge')
                    ->label('Rozet')
                    ->placeholder('Çok satan')
                    ->rules(['nullable', 'max:40'])
                    // Gerçek rozetler kısa ("Çok satan", "Klasik"); 40 karakter
                    // sınırına göre genişlik ayarlamak sütunu boş yere şişiriyordu.
                    // Uzun metin girilirse alanın içinde kayar, kırpılmaz.
                    // Fiyatla aynı taban kaldırma gerekiyor (theme.blade.php).
                    ->width('8rem')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Güncellendi')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Satışta')
                    ->placeholder('Hepsi')
                    ->trueLabel('Satışta olanlar')
                    ->falseLabel('Yayından kaldırılanlar'),

                TernaryFilter::make('is_featured')
                    ->label('Öne çıkan')
                    ->placeholder('Hepsi')
                    ->trueLabel('Öne çıkanlar')
                    ->falseLabel('Öne çıkmayanlar'),
            ])
            ->recordActions([
                // Vitrindeki hâlini yeni sekmede açar. Taslaklar da açılır:
                // ShopController::product yayında olmayan ürünü ekibe
                // gösteriyor (misafir/müşteri için 404 sürüyor). Canlıda
                // yapım aşamasında perdesi açıkken de çalışır, perdeyi ekip
                // girişi geçiyor (MaintenanceMode).
                // Yalnız göz ikonu. `label` SİLİNMEDİ, gizlendi: ikon düğmesi
                // görünümünde etiket erişilebilirlik adı olarak kullanılıyor,
                // kaldırılsa ekran okuyucuda adsız düğme kalırdı. Ne yaptığı
                // fareyle üzerine gelince ipuçlarında yazıyor.
                Action::make('onizleme')
                    ->label('Önizleme')
                    ->icon('heroicon-m-eye')
                    ->iconButton()
                    ->tooltip('Vitrinde önizle')
                    ->url(fn (Product $record): string => route('shop.product', $record->slug))
                    ->openUrlInNewTab(),

                // Kategori ekle/çıkar — ürün formunu açmadan.
                //
                // Neden satır eylemi ve neden hücreye tıklama DEĞİL: Filament'in
                // `Column::action()`'ı bir Action nesnesi kabul ediyor gibi
                // görünse de `callTableColumnAction` yalnız Closure çalıştırıyor,
                // Action nesnesinde sessizce null dönüyor — tıklama hiçbir şey
                // yapmaz. Kip (modal) açmanın çalışan yolu satır eylemi.
                //
                // `relationship()` yerine `options()` + elle `sync()`: eylem
                // formlarında ilişki kaydetme otomatik çalışmıyor, yalnız
                // doldurma çalışıyor. Yükleme `fillForm` ile.
                Action::make('kategoriler')
                    ->label('Kategoriler')
                    ->modalHeading('Kategorileri düzenle')
                    ->modalSubmitActionLabel('Kaydet')
                    ->fillForm(fn (Product $record): array => [
                        'categories' => $record->categories->pluck('id')->all(),
                    ])
                    ->schema([
                        Select::make('categories')
                            ->label('Kategoriler')
                            ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            // Formdaki kuralla aynı: ürün kategorisiz kalmasın,
                            // yoksa mağaza filtrelerinden düşer.
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $record->categories()->sync($data['categories'] ?? []);
                    }),

                EditAction::make()->label('Düzenle'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ])
            ->emptyStateHeading('Henüz ürün yok')
            ->emptyStateDescription('İlk ürününüzü ekleyerek başlayın.');
    }
}
