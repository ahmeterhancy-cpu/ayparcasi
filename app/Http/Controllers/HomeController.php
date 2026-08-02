<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Faq;
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

        return view('home', [
            'occasions' => $occasions,
            'featured' => $featured,
            // "Yeni Çiçekler" vitrini de $newest'ten besleniyor; ayrı sorgu yok
            'newest' => $newest,
            'showcase' => Banner::live('showcase')->first(),
            'zones' => DeliveryZone::active()->orderBy('position')->get(),
            'promos' => Banner::live('promo')->limit(3)->get(),
            'testimonials' => Testimonial::active()->limit(6)->get(),
            'faqs' => Faq::active()->limit(6)->get(),
            'posts' => Post::published()->limit(3)->get(),
        ]);
    }
}
