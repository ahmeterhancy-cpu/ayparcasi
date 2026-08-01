<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\StockInquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /**
     * "WhatsApp'tan stok bilgisi al" — tıklamayı kaydeder ve
     * hazır mesajlı WhatsApp bağlantısını döndürür.
     */
    public function stock(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'exists:products,id'],
            'variant_name' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'in:product,listing'],
        ]);

        $product = ! empty($data['product_id']) ? Product::find($data['product_id']) : null;

        StockInquiry::create([
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'variant_name' => $data['variant_name'] ?? null,
            'source' => $data['source'] ?? 'product',
            'ip' => $request->ip(),
        ]);

        $message = $product
            ? "Merhaba, ayparcasicicekci.com'da gördüğüm \"{$product->name}\""
                .(! empty($data['variant_name']) ? " ({$data['variant_name']})" : '')
                .' ürününün stok durumunu öğrenebilir miyim? '.$product->url
            : 'Merhaba, bir ürünün stok durumunu öğrenmek istiyorum.';

        return response()->json(['url' => wa_link($message)]);
    }

    public function newsletter(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['name' => $data['name'] ?? null, 'is_active' => true],
        );

        return back()->with('success', 'Teşekkürler! Yeni koleksiyonlardan ilk siz haberdar olacaksınız.');
    }
}
