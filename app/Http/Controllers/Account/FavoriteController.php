<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index()
    {
        return view('account.favorites', [
            'products' => Auth::user()
                ->favorites()
                ->with('variants', 'categories')
                ->where('products.is_active', true)
                ->orderByDesc('favorites.created_at')
                ->get(),
        ]);
    }

    /** Kalp düğmesi — ekli değilse ekler, ekliyse çıkarır. */
    public function toggle(Product $product)
    {
        $user = Auth::user();

        if ($user->favorites()->whereKey($product->id)->exists()) {
            $user->favorites()->detach($product->id);
            $favorited = false;
        } else {
            $user->favorites()->attach($product->id);
            $favorited = true;
        }

        return response()->json([
            'favorited' => $favorited,
            'count' => $user->favorites()->count(),
        ]);
    }

    /**
     * Giriş yapmadan önce tarayıcıda biriktirilen favorileri hesaba taşır.
     * Aynı istek tekrar gelse de sonuç değişmez.
     */
    public function merge(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $ids = Product::whereIn('id', $data['ids'])->where('is_active', true)->pluck('id');

        Auth::user()->favorites()->syncWithoutDetaching($ids);

        return response()->json([
            'ids' => Auth::user()->favorites()->pluck('products.id'),
        ]);
    }
}
