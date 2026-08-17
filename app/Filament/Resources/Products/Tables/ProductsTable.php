<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                // Sabit genişlik: ürün adları ("Beyaz Falenopsis Orkide")
                // 18rem'e rahat sığıyor. Emici sütun artık bu DEĞİL — artan
                // genişliği kategori alıyor, yoksa bu alan tabloyu yiyordu.
                // İç `min-width` bilerek yok: genişliği tek yerden, sütundan
                // yönetiyoruz (taban olarak Filament'in 12rem'i yeterli).
                TextInputColumn::make('name')
                    ->label('Ürün')
                    ->searchable()
                    ->sortable()
                    ->width('18rem')
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
