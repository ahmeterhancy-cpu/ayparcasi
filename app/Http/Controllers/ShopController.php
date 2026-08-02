<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->active()->with('variants', 'categories');

        return view('shop.index', $this->listing($query, $request, null));
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        $query = $category->allProducts()->with('variants', 'categories');

        return view('shop.index', $this->listing($query, $request, $category));
    }

    /**
     * Liste sayfalarının ortak verisi: filtre + sıralama + sayfalama.
     *
     * @return array<string, mixed>
     */
    private function listing($query, Request $request, ?Category $category, ?string $term = null): array
    {
        $bounds = Product::active()->selectRaw('MIN(price) as lo, MAX(price) as hi')->first();

        return [
            'category' => $category,
            'term' => $term,
            'products' => $this->sorted($this->filtered($query, $request), $request)
                ->paginate(12)
                ->withQueryString(),
            'categories' => Category::active()->roots()->with('children')->orderBy('position')->get(),
            'sort' => $request->string('sirala')->toString(),
            'filters' => $this->activeFilters($request),
            'priceBounds' => [
                'lo' => (int) floor((float) ($bounds->lo ?? 0)),
                'hi' => (int) ceil((float) ($bounds->hi ?? 0)),
            ],
        ];
    }

    private function filtered($query, Request $request)
    {
        if ($min = $request->integer('min')) {
            $query->where('price', '>=', $min);
        }

        if ($max = $request->integer('max')) {
            $query->where('price', '<=', $max);
        }

        if ($request->boolean('indirimli')) {
            $query->onSale();
        }

        if ($request->boolean('ayni_gun')) {
            $query->where('same_day', true);
        }

        if ($request->boolean('stokta')) {
            $query->inStock();
        }

        return $query;
    }

    /**
     * Vitrinde çip olarak gösterilecek aktif filtreler.
     *
     * @return array<int, array{label: string, key: string}>
     */
    private function activeFilters(Request $request): array
    {
        $out = [];

        if ($min = $request->integer('min')) {
            $out[] = ['label' => money($min).' ve üzeri', 'key' => 'min'];
        }

        if ($max = $request->integer('max')) {
            $out[] = ['label' => money($max).' ve altı', 'key' => 'max'];
        }

        foreach ([
            'indirimli' => 'İndirimdekiler',
            'ayni_gun' => 'Aynı gün teslim',
            'stokta' => 'Stokta olanlar',
        ] as $key => $label) {
            if ($request->boolean($key)) {
                $out[] = ['label' => $label, 'key' => $key];
            }
        }

        return $out;
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->input('q'));

        $query = Product::query()->active()->with('variants', 'categories');

        if ($term !== '') {
            $like = '%'.str_replace('%', '\%', $term).'%';
            $query->where(fn ($q) => $q
                ->where('name', 'like', $like)
                ->orWhere('short_description', 'like', $like)
                ->orWhere('contents', 'like', $like));
        }

        return view('shop.index', $this->listing($query, $request, null, $term));
    }

    public function product(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('variants', 'categories');

        $related = Product::query()
            ->active()
            ->with('variants')
            ->whereKeyNot($product->id)
            ->when($product->categories->isNotEmpty(), fn ($q) => $q->whereHas(
                'categories',
                fn ($c) => $c->whereIn('categories.id', $product->categories->pluck('id'))
            ))
            ->inRandomOrder()
            ->limit(4)
            ->get();

        $user = $request->user();

        return view('shop.product', [
            'product' => $product,
            'related' => $related,
            'addons' => $product->addons()->active()->get(),
            'reviews' => $product->approvedReviews()->get(),
            'breakdown' => $product->ratingBreakdown(),
            // Yorum formu yalnızca hakkı olana gösterilir; asıl kontrol
            // ReviewController'da tekrar yapılır.
            'canReview' => (bool) $product->reviewableOrderFor($user),
            'myReview' => $product->reviewBy($user),
        ]);
    }

    /**
     * Hızlı bakış — düzen olmadan yalnızca içerik parçası döner,
     * kart üzerindeki göz düğmesi bunu pencereye yükler.
     */
    public function quickView(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('variants', 'categories');

        return view('shop.quickview', [
            'product' => $product,
            'addons' => $product->addons()->active()->get(),
        ]);
    }

    private function sorted($query, Request $request)
    {
        return match ($request->string('sirala')->toString()) {
            'ucuz' => $query->orderBy('price'),
            'pahali' => $query->orderByDesc('price'),
            'yeni' => $query->latest('id'),
            default => $query->orderBy('position')->orderByDesc('is_featured')->latest('id'),
        };
    }
}
