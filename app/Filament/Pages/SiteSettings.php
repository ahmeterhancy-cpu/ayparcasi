<?php

namespace App\Filament\Pages;

use App\Mail\TestMail;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
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
        'order_emails_enabled', 'order_alert_email',
        'map_lat', 'map_lng',
        'hero_eyebrow', 'hero_title', 'hero_subtitle', 'hero_image', 'hero_image_2', 'hero_image_3',
        'video_title', 'video_text', 'video_points', 'video_poster', 'video_file',
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

                                TextInput::make('map_lat')
                                    ->label('Harita — enlem')
                                    ->placeholder('35.3361986')
                                    ->helperText('Google Haritalar üzerinde dükkâna sağ tıklayıp koordinatları kopyalayın. Boş bırakırsanız harita hiç görünmez.'),
                                TextInput::make('map_lng')
                                    ->label('Harita — boylam')
                                    ->placeholder('33.3224065'),
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

                                FileUpload::make('hero_image_2')
                                    ->label('2. fotoğraf (isteğe bağlı)')
                                    ->image()->imageEditor()->directory('site')->disk('public')->maxSize(8192)
                                    ->helperText('Eklerseniz fotoğraflar mozaik geçişle sırayla değişir.'),

                                FileUpload::make('hero_image_3')
                                    ->label('3. fotoğraf (isteğe bağlı)')
                                    ->image()->imageEditor()->directory('site')->disk('public')->maxSize(8192),
                            ]),

                            Section::make('Tanıtım videosu')
                                ->description('Boş bırakırsanız bu bölüm ana sayfada hiç görünmez.')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('video_title')->label('Başlık')->columnSpanFull()
                                        ->placeholder('Sevdiklerinizi mutlu etmenin en güzel yolu'),
                                    Textarea::make('video_text')->label('Alt metin')->rows(3)->columnSpanFull(),
                                    Textarea::make('video_points')->label('Maddeler')->rows(4)->columnSpanFull()
                                        ->helperText('Her satır bir madde olur. Boş bırakabilirsiniz.'),

                                    FileUpload::make('video_poster')
                                        ->label('Kapak görseli')
                                        ->image()
                                        ->imageEditor()
                                        ->directory('site')
                                        ->disk('public')
                                        ->maxSize(8192)
                                        ->helperText('Video oynatılmadan önce görünen kare.'),

                                    FileUpload::make('video_file')
                                        ->label('Video dosyası')
                                        ->acceptedFileTypes(['video/mp4', 'video/webm'])
                                        ->directory('site')
                                        ->disk('public')
                                        ->maxSize(51200)
                                        ->helperText('MP4 ya da WebM, en fazla 50 MB. Kısa tutun (20–40 sn).'),
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

                            Section::make('E-posta bildirimleri')
                                ->columns(2)
                                ->description('Müşteriye sipariş özeti ve durum bildirimleri, ekibe yeni sipariş uyarısı gönderilir. Müşteri e-posta bırakmadıysa yalnızca ekip bildirimi gider.')
                                ->schema([
                                    Toggle::make('order_emails_enabled')
                                        ->label('Sipariş e-postaları gönderilsin')
                                        ->default(true)
                                        ->columnSpanFull(),

                                    TextInput::make('order_alert_email')
                                        ->label('Yeni sipariş bildirimi gitsin')
                                        ->email()
                                        ->helperText('Boş bırakırsanız mağaza e-postasına gider.')
                                        ->columnSpanFull(),
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_eposta')
                ->label('Test e-postası gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->modalHeading('E-posta ayarlarını dene')
                ->modalDescription('Gerçek bir sipariş e-postası şablonuyla deneme gönderimi yapar. SMTP ayarlarınızı doğrulamak için kullanın.')
                ->modalSubmitActionLabel('Gönder')
                ->schema([
                    TextInput::make('to')
                        ->label('Alıcı adresi')
                        ->email()
                        ->required()
                        ->default(fn () => setting('order_alert_email') ?: setting('email')),
                ])
                ->action(function (array $data) {
                    try {
                        Mail::to($data['to'])->send(new TestMail);

                        Notification::make()
                            ->title('Test e-postası gönderildi')
                            ->body($data['to'].' adresini kontrol edin. Yerel kurulumda posta storage/logs/laravel.log dosyasına yazılır.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Gönderilemedi')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
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
