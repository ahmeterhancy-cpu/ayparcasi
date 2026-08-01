<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\TikoGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly TikoGateway $tiko) {}

    /** Müşteriyi Tiko 3D sayfasına gönderen otomatik form. */
    public function redirect(Request $request, Order $order)
    {
        abort_unless($this->owns($request, $order), 404);

        if ($order->payment_status === 'paid') {
            return redirect()->route('order.show', $order->number);
        }

        if (! $this->tiko->isConfigured()) {
            return redirect()
                ->route('order.show', $order->number)
                ->with('error', 'Kart ile ödeme şu an kullanılamıyor. Sizinle iletişime geçeceğiz.');
        }

        return view('payment.redirect', [
            'order' => $order,
            'action' => $this->tiko->endpoint(),
            'fields' => $this->tiko->formFields($order),
        ]);
    }

    /** Tiko'nun tarayıcıyı geri gönderdiği adres. Karar callback'te verilir. */
    public function handleReturn(Request $request, Order $order)
    {
        abort_unless($this->owns($request, $order), 404);

        $order->refresh();

        return redirect()->route('order.show', $order->number)->with(
            $order->payment_status === 'paid' ? 'success' : 'info',
            $order->payment_status === 'paid'
                ? 'Ödemeniz alındı, teşekkür ederiz.'
                : 'Ödeme sonucunuzu doğruluyoruz. Bu sayfa siparişinizin güncel durumunu gösterir.'
        );
    }

    /** Tiko sunucudan sunucuya bildirim. */
    public function callback(Request $request)
    {
        $order = $this->tiko->handleCallback($request);

        return response($order ? 'OK' : 'FAIL', $order ? 200 : 400);
    }

    private function owns(Request $request, Order $order): bool
    {
        return in_array($order->number, (array) $request->session()->get('my_orders', []), true);
    }
}
