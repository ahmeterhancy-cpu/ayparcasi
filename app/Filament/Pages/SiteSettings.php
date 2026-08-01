<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Site ayarları — key/value tablosuna yazan tek sayfalık form.
 */
class SiteSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Site ayarları';

    protected static ?string $title = 'Site ayarları';

    protected string $view = 'filament.pages.site-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    /** Formda yönetilen ayar anahtarları. */
    private const KEYS = [
        'shop_name', 'tagline', 'meta_description',
        'phone', 'whatsapp', 'email', 'address', 'hours',
        'instagram', 'facebook',
        'same_day_cutoff_hour', 'bank_details',
        'low_stock_threshold', 'low_stock_email',
        'hero_eyebrow', 'hero_title', 'hero_subtitle', 'hero_image',
        'about_title', 'about_text', 'about_image',
        'footer_text',
    ];

    public function mount(): void
    {
        $values = [];

        foreach (self::KEYS as $key) {
            $values[$key] = Setting::get($key);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()->tabs([

                    Tab::make('Dükkân')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([
                            Section::make()->columns(2)->schema([
                                TextInput::make('shop_name')->label('Mağaza adı')->required(),
                                TextInput::make('tagline')->label('Slogan')
                                    ->helperText('Logonun altında görünür.'),
                                Textarea::make('meta_description')
                                    ->label('Site açıklaması (SEO)')
                                    ->rows(2)
                                    ->maxLength(300)
                                    ->columnSpanFull(),
                                Textarea::make('footer_text')
                                    ->label('Alt bilgi metni')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    Tab::make('İletişim')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Section::make()->columns(2)->schema([
                                TextInput::make('phone')->label('Telefon')->tel(),
                                TextInput::make('whatsapp')
                                    ->label('WhatsApp numarası')
                                    ->helperText('Ülke koduyla, yalnızca rakam. Örn: 905330000000')
                                    ->required(),
                                TextInput::make('email')->label('E-posta')->email(),
                                TextInput::make('hours')->label('Çalışma saatleri')
                                    ->placeholder('Her gün 09:00 – 19:00'),
                                Textarea::make('address')->label('Adres')->rows(2)->columnSpanFull(),
                                TextInput::make('instagram')->label('Instagram adresi')->url(),
                                TextInput::make('facebook')->label('Facebook adresi')->url(),
                            ]),
                        ]),

                    Tab::make('Ana sayfa')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Section::make('Hero')->columns(2)->schema([
                                TextInput::make('hero_eyebrow')->label('Üst etiket')
                                    ->placeholder('Kıbrıs · Aynı gün teslimat'),
                                TextInput::make('hero_title')->label('Başlık')->columnSpanFull(),
                                Textarea::make('hero_subtitle')->label('Alt metin')->rows(2)->columnSpanFull(),
                                FileUpload::make('hero_image')
                                    ->label('Hero görseli')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('site')
                                    ->disk('public')
                                    ->maxSize(8192)
                                    ->columnSpanFull()
                                    ->helperText('Geniş ve yatay bir fotoğraf seçin (en az 2000px).'),
                            ]),
                        ]),

                    Tab::make('Hakkımızda')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make()->schema([
                                TextInput::make('about_title')->label('Başlık'),
                                Textarea::make('about_text')->label('Metin')->rows(10)
                                    ->helperText('Paragrafları boş satırla ayırın.'),
                                FileUpload::make('about_image')
                                    ->label('Görsel')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('site')
                                    ->disk('public')
                                    ->maxSize(8192),
                            ]),
                        ]),

                    Tab::make('Sipariş')
                        ->icon('heroicon-o-truck')
                        ->schema([
                            Section::make()->schema([
                                TextInput::make('same_day_cutoff_hour')
                                    ->label('Aynı gün teslimat kapanış saati')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(23)
                                    ->required()
                                    ->suffix(':00')
                                    ->helperText('Ana sayfadaki geri sayım ve kasadaki tarih kontrolü bunu kullanır.'),

                                Textarea::make('bank_details')
                                    ->label('Havale / EFT bilgileri')
                                    ->rows(4)
                                    ->helperText('Havaleyle ödeme seçildiğinde sipariş sayfasında gösterilir.'),
                            ]),

                            Section::make('Stok uyarısı')->columns(2)->schema([
                                TextInput::make('low_stock_threshold')
                                    ->label('Uyarı eşiği')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(3)
                                    ->suffix('adet')
                                    ->helperText('Stok bu sayıya inince e-posta gönderilir.'),

                                TextInput::make('low_stock_email')
                                    ->label('Uyarı e-postası')
                                    ->email()
                                    ->helperText('Boş bırakırsanız mağaza e-postasına gider.'),
                            ]),
                        ]),
                ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::putMany(collect($data)
            ->only(self::KEYS)
            ->map(fn ($v) => is_array($v) ? ($v[0] ?? null) : $v)
            ->all());

        Notification::make()
            ->title('Ayarlar kaydedildi.')
            ->success()
            ->send();
    }
}
