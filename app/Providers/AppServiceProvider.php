<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Services\Cart;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Cart::class, fn ($app) => new Cart($app['session.store']));

        // Giriş yapmış müşterinin favori ürün kimlikleri — istek başına tek sorgu.
        // Ürün kartları bunu okuyup kalbi dolu gösterir (N+1 olmaz).
        $this->app->scoped('favorites.ids', function () {
            if (! Schema::hasTable('favorites') || ! auth()->check()) {
                return [];
            }

            return auth()->user()->favorites()->pluck('products.id')->all();
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Carbon::setLocale('tr');
        Paginator::defaultView('vendor.pagination.ayparcasi');
        Paginator::defaultSimpleView('vendor.pagination.ayparcasi');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Vitrinin her sayfasında lazım olanlar
        View::composer(['components.layouts.*', 'partials.*'], function ($view) {
            $view->with([
                'navCategories' => $this->navCategories(),
                'cartCount' => app(Cart::class)->count(),
                'announcement' => $this->announcement(),
            ]);
        });
    }

    private function navCategories()
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }

        return Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
            ->orderBy('position')
            ->get();
    }

    private function announcement(): ?Banner
    {
        if (! Schema::hasTable('banners')) {
            return null;
        }

        return Banner::live('strip')->first();
    }
}
