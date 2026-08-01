<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;

/**
 * Yazdırılabilir belgeler. Tarayıcının "PDF olarak kaydet" seçeneğiyle
 * dosya da üretilebilir; ayrı bir PDF kütüphanesine gerek yok.
 */
class PrintController extends Controller
{
    private function guard(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['admin', 'staff'], true), 403);
    }

    /** Müşteri fişi — kalemler ve tutarlar. */
    public function receipt(Order $order)
    {
        $this->guard();

        return view('print.receipt', ['order' => $order->load('items', 'refunds')]);
    }

    /** Kurye teslim fişi — adres, telefon, kart notu iri puntoyla. */
    public function slip(Order $order)
    {
        $this->guard();

        return view('print.slip', ['order' => $order->load('items')]);
    }

    /** Günün tüm teslimatları tek sayfada. */
    public function daySheet()
    {
        $this->guard();

        $orders = Order::query()
            ->whereDate('delivery_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->with('items')
            ->orderBy('delivery_slot')
            ->orderBy('delivery_zone_name')
            ->get();

        return view('print.day', ['orders' => $orders]);
    }
}
