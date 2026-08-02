<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index()
    {
        return view('cart', [
            'lines' => $this->cart->lines(),
            'summary' => $this->cart->summary(),
            'zones' => DeliveryZone::active()->orderBy('position')->get(),
            'suggested' => Product::active()->featured()->with('variants')->inRandomOrder()->limit(4)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'exists:addons,id'],
        ]);

        $product = Product::with('variants')->findOrFail($data['product_id']);

        abort_unless($product->is_active, 404);

        if (! $product->is_orderable) {
            return back()->with('error', 'Bu ürün şu an tükendi. WhatsApp\'tan stok durumunu sorabilirsiniz.');
        }

        $variant = null;

        if (! empty($data['variant_id'])) {
            $variant = $product->variants->firstWhere('id', (int) $data['variant_id']);

            if (! $variant || ! $variant->is_active) {
                return back()->with('error', 'Seçtiğiniz boy artık mevcut değil.');
            }
        } elseif ($product->has_variants) {
            $variant = $product->variants->firstWhere('is_default', true)
                ?? $product->variants->where('is_active', true)->sortBy('price')->first();
        }

        // Ek ürünler artık ürüne bağlı. Formdan gelen kimlikler yalnızca bu
        // ürüne seçilmiş ve aktif olanlarla kesiştirilir — istek elle
        // düzenlenip başka bir ürünün eki sepete sokulamasın.
        $allowed = $product->addons()->active()->pluck('addons.id');

        $addonIds = collect($data['addons'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->intersect($allowed)
            ->values()
            ->all();

        $this->cart->add($product, $variant, (int) ($data['quantity'] ?? 1), $addonIds);

        return redirect()
            ->route('cart.index')
            ->with('success', $product->name.' sepete eklendi.');
    }

    public function update(Request $request, string $key)
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);

        $this->cart->update($key, (int) $request->input('quantity'));

        return back();
    }

    public function destroy(string $key)
    {
        $this->cart->remove($key);

        return back()->with('success', 'Ürün sepetten çıkarıldı.');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:60']]);

        $error = $this->cart->applyCoupon($request->string('code')->toString());

        return $error
            ? back()->with('error', $error)
            : back()->with('success', 'Kupon uygulandı.');
    }

    public function removeCoupon()
    {
        $this->cart->removeCoupon();

        return back()->with('success', 'Kupon kaldırıldı.');
    }
}
