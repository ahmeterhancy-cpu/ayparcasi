<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Models\Faq;
use App\Models\Post;
use App\Models\Testimonial;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about', [
            'testimonials' => Testimonial::active()->limit(6)->get(),
        ]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function delivery()
    {
        return view('pages.delivery', [
            'zones' => DeliveryZone::active()->orderBy('position')->get(),
            'faqs' => Faq::active()->get(),
        ]);
    }

    public function faq()
    {
        return view('pages.faq', [
            'faqs' => Faq::active()->get(),
        ]);
    }

    public function blog()
    {
        return view('pages.blog', [
            'posts' => Post::published()->paginate(9),
        ]);
    }

    public function post(Post $post)
    {
        abort_unless($post->is_active && $post->published_at && $post->published_at->isPast(), 404);

        return view('pages.post', [
            'post' => $post,
            'more' => Post::published()->whereKeyNot($post->id)->limit(3)->get(),
        ]);
    }
}
