<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = [];

        $add = function (string $loc, ?string $lastmod = null, string $freq = 'weekly', string $priority = '0.6') use (&$urls) {
            $urls[] = compact('loc', 'lastmod', 'freq', 'priority');
        };

        $add(route('home'), null, 'daily', '1.0');
        $add(route('shop.index'), null, 'daily', '0.9');
        $add(route('page.about'), null, 'monthly', '0.4');
        $add(route('page.contact'), null, 'monthly', '0.4');
        $add(route('page.delivery'), null, 'monthly', '0.5');
        $add(route('page.faq'), null, 'monthly', '0.4');
        $add(route('page.blog'), null, 'weekly', '0.5');

        Category::active()->orderBy('position')->get()
            ->each(fn (Category $c) => $add(route('shop.category', $c->slug), $c->updated_at?->toAtomString(), 'weekly', '0.8'));

        Product::active()->orderBy('id')->chunk(500, function ($products) use ($add) {
            foreach ($products as $p) {
                $add(route('shop.product', $p->slug), $p->updated_at?->toAtomString(), 'weekly', '0.7');
            }
        });

        Post::published()->get()
            ->each(fn (Post $p) => $add(route('page.post', $p->slug), $p->updated_at?->toAtomString(), 'monthly', '0.5'));

        $body = collect($urls)->map(function (array $u) {
            $lastmod = $u['lastmod'] ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '';

            return '<url>'
                .'<loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'
                .$lastmod
                .'<changefreq>'.$u['freq'].'</changefreq>'
                .'<priority>'.$u['priority'].'</priority>'
                .'</url>';
        })->implode("\n");

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$body."\n"
            .'</urlset>';
    }
}
