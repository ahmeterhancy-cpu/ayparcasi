<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        $occasions = Category::query()
            ->active()
            ->where('is_featured', true)
            ->orderBy('position')
            ->limit(8)
            ->get();

        $featured = Product::query()
            ->active()
            ->featured()
            ->with('variants')
            ->orderBy('position')
            ->limit(8)
            ->get();

        $newest = Product::query()
            ->active()
            ->with('variants')
            ->latest('id')
            ->limit(8)
            ->get();

        // "Orkideler" öne çıkan koleksiyon bloğu için
        $spotlight = Category::query()->active()->where('slug', 'orkideler')->first()
            ?? Category::query()->active()->roots()->orderBy('position')->first();

        $spotlightProducts = $spotlight
            ? $spotlight->allProducts()->with('variants')->limit(4)->get()
            : collect();

        return view('home', [
            'occasions' => $occasions,
            'featured' => $featured,
            'newest' => $newest,
            'spotlight' => $spotlight,
            'spotlightProducts' => $spotlightProducts,
            'zones' => DeliveryZone::active()->orderBy('position')->get(),
            'stats' => [
                'products' => Product::active()->count(),
                'orders' => max(240, Order::where('status', 'delivered')->count()),
                'zones' => DeliveryZone::active()->count(),
            ],
            'promos' => Banner::live('promo')->limit(3)->get(),
            'testimonials' => Testimonial::active()->limit(6)->get(),
            'faqs' => Faq::active()->limit(6)->get(),
            'posts' => Post::published()->limit(3)->get(),
            'gallery' => Product::query()
                ->active()
                ->whereNotNull('hero_image')
                ->inRandomOrder()
                ->limit(12)
                ->pluck('hero_image'),
        ]);
    }
}
