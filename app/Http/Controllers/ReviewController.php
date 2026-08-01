<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $user = Auth::user();

        // Yorum hakkı yalnızca bu ürünü sipariş edip teslim almış müşterinin.
        // Kontrol burada yapılır; formun görünmesine güvenilmez.
        $order = $product->reviewableOrderFor($user);

        if (! $order) {
            return back()
                ->with('error', 'Yorum yazabilmek için bu ürünü sipariş etmiş ve teslim almış olmanız gerekiyor.')
                ->withFragment('yorumlar');
        }

        if ($product->reviewBy($user)) {
            return back()
                ->with('error', 'Bu ürün için zaten bir yorumunuz var.')
                ->withFragment('yorumlar');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:1000'],
        ], [], [
            'rating' => 'puan',
            'title' => 'başlık',
            'body' => 'yorum',
        ]);

        Review::create([
            ...$data,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'name' => $user->name,
            'status' => 'pending',
        ]);

        return back()
            ->with('success', 'Yorumunuz alındı. Ekibimiz onayladıktan sonra sayfada görünecek.')
            ->withFragment('yorumlar');
    }
}
