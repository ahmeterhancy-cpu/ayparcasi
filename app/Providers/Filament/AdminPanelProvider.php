<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Widgets\ShopOverview;
use App\Filament\Widgets\UpcomingDeliveries;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile()
            ->brandName('Ay Parçası')
            // Gerçek logo. Görünüm bağlama göre değişir — bkz. filament/brand.
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.1rem')
            ->favicon(asset('favicon.svg'))
            ->font('Manrope', provider: LocalFontProvider::class)
            /*
             * Renkler elle yazılmış ton merdivenleri — `Color::hex()` KULLANMA:
             * o yalnızca rengin hue'sunu alıp kendi sabit açıklık/doygunluk
             * rampasını kurar, markanın koyu petrolü parlak camgöbeğine döner.
             * Filament açık temada düğme zemini için 400, yazı için 950 tonunu
             * kullanır; o iki basamak markanın gerçek renkleriyle sabitlendi.
             */
            ->colors([
                'primary' => [
                    50 => '#f1fafa',
                    100 => '#d6f0ee',  // --turq-3
                    200 => '#b2e5e5',
                    300 => '#8fd8d6',  // --turq-2
                    400 => '#4cbfc4',  // --turq — düğme zemini
                    500 => '#2a9fac',
                    600 => '#16697f',  // --sea — bağlantı, ikon, koyu tema düğmesi
                    700 => '#12586b',
                    // Açık temada düğme yazısı bu tondan geliyor; turkuaz
                    // zeminle kontrastı AA sınırının üstünde kalsın diye koyu.
                    800 => '#0f3f4d',
                    900 => '#0e2c34',  // --ink
                    950 => '#081c22',
                ],
                'warning' => [
                    50 => '#fef8ea',
                    100 => '#fdefcb',
                    200 => '#fbdf93',
                    300 => '#f8c95c',
                    400 => '#f4b02a',  // --sun
                    500 => '#e09a12',
                    600 => '#dd8f0c',  // --sun-deep
                    700 => '#a9690c',
                    800 => '#895410',
                    900 => '#714611',
                    950 => '#3f2405',
                ],
                'danger' => [
                    50 => '#fdf4f2',
                    100 => '#fbe4df',
                    200 => '#f7cabf',
                    300 => '#f0a795',
                    400 => '#e57d64',
                    500 => '#db4a32',  // --coral
                    600 => '#c53c25',
                    700 => '#a42f1d',
                    800 => '#872b1c',
                    900 => '#70281c',
                    950 => '#3c110a',
                ],
                'success' => [
                    50 => '#f0f9f4',
                    100 => '#daf0e3',
                    200 => '#b6e1c9',
                    300 => '#86caa7',
                    400 => '#55ad83',
                    500 => '#389068',
                    600 => '#2f7d5c',  // --ok
                    700 => '#26634a',
                    800 => '#204f3d',
                    900 => '#1c4133',
                    950 => '#0c241b',
                ],
                'info' => [
                    50 => '#faf4f7',
                    100 => '#f3e6ec',
                    200 => '#e7ccd9',
                    300 => '#d3a7bd',
                    400 => '#b87b99',
                    500 => '#9c5678',
                    600 => '#7b3f5e',  // --plum
                    700 => '#66344d',
                    800 => '#552d41',
                    900 => '#48283a',
                    950 => '#28131f',
                ],
                // Sıcak nötr — vitrinin krem kâğıdıyla aynı ailede
                'gray' => [
                    50 => '#faf9f6',
                    100 => '#f3f1eb',
                    200 => '#e7e2d9',
                    300 => '#d4cdc0',
                    400 => '#a8a094',
                    500 => '#7c7469',
                    600 => '#625b52',
                    700 => '#504a43',
                    800 => '#423d37',
                    900 => '#393430',
                    950 => '#221f1c',
                ],
            ])
            // Marka katmanı: fontlar + düz CSS (Tailwind sınıfı çalışmaz)
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.theme'))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.auth-foot'))
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'Satış',
                'Katalog',
                'Teslimat',
                'İçerik',
                'Ayarlar',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ShopOverview::class,
                UpcomingDeliveries::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
