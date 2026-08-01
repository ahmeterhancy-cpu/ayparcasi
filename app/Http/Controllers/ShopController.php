<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->active()->with('variants', 'categories');

        return view('shop.index', [
            'category' => null,
            'products' => $this->sorted($query, $request)->paginate(12)->withQueryString(),
            'categories' => Category::active()->roots()->with('children')->orderBy('position')->get(),
            'sort' => $request->string('sirala')->toString(),
        ]);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        $query = $category->allProducts()->with('variants', 'categories');

        return view('shop.index', [
            'category' => $category,
            'products' => $this->sorted($query, $request)->paginate(12)->withQueryString(),
            'categories' => Category::active()->roots()->with('children')->orderBy('position')->get(),
            'sort' => $request->string('sirala')->toString(),
        ]);
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

        return view('shop.index', [
            'category' => null,
            'term' => $term,
            'products' => $this->sorted($query, $request)->paginate(12)->withQueryString(),
            'categories' => Category::active()->roots()->with('children')->orderBy('position')->get(),
            'sort' => $request->string('sirala')->toString(),
        ]);
    }

    public function product(Product $product)
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

        return view('shop.product', [
            'product' => $product,
            'related' => $related,
            'addons' => Addon::active()->orderBy('position')->get(),
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
