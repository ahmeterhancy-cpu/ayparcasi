<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Cart;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        return view('account.orders', [
            'orders' => Auth::user()->orders()->with('items')->paginate(10),
        ]);
    }

    public function show(Order $order)
    {
        // Yalnızca kendi siparişi
        abort_unless($order->user_id === Auth::id(), 404);

        $order->load('items.product');

        return view('account.order', [
            'order' => $order,
            'whatsappUrl' => wa_link(
                "Merhaba, {$order->number} numaralı siparişim hakkında bilgi alabilir miyim?"
            ),
        ]);
    }

    /** Aynı siparişi tekrar sepete koy. */
    public function reorder(Order $order, Cart $cart)
    {
        abort_unless($order->user_id === Auth::id(), 404);

        $added = 0;
        $skipped = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_active || ! $product->is_orderable) {
                $skipped[] = $item->name;

                continue;
            }

            $variant = $item->variant_name
                ? $product->variants()->where('name', $item->variant_name)->where('is_active', true)->first()
                : null;

            $cart->add($product, $variant, $item->quantity);
            $added++;
        }

        if ($added === 0) {
            return back()->with('error', 'Bu siparişteki ürünlerin hiçbiri şu an satışta değil.');
        }

        return redirect()->route('cart.index')->with(
            'success',
            $skipped
                ? $added.' ürün sepete eklendi. Şunlar eklenemedi: '.implode(', ', $skipped)
                : 'Siparişteki ürünler sepete eklendi.'
        );
    }
}
