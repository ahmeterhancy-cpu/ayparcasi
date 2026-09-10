<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\TikoGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly TikoGateway $tiko) {}

    /**
     * Kart ödeme sayfası. Tiko'dan alınan bağlantı iFrame'de açılır —
     * kart bilgisi bizim sayfamıza HİÇ girilmez.
     */
    public function redirect(Request $request, Order $order)
    {
        abort_unless($this->owns($request, $order), 404);

        if ($order->payment_status === 'paid') {
            return redirect()->route('order.show', $order->number);
        }

        $link = $this->tiko->createPaymentLink($order);

        if (! $link) {
            return redirect()
                ->route('order.show', $order->number)
                ->with('error', 'Kart ile ödeme şu an kullanılamıyor. Siparişiniz kaydedildi, sizinle iletişime geçeceğiz.');
        }

        return view('payment.tiko', [
            'order' => $order,
            'link' => $link,
        ]);
    }

    /**
     * Tiko'nun iFrame içinden POST ettiği dönüş adresi.
     *
     * Gelen alanlar (Status, TransId, Hash) SONUÇ SAYILMAZ — belge bunu
     * açıkça söylüyor. Kararı sunucudan sunucuya sorgu veriyor.
     *
     * Bu yolda oturum middleware'i KAPALI (bkz. routes/web.php), o yüzden
     * burada `session()` ya da `Auth` kullanılamaz.
     */
    public function handleReturn(Request $request, Order $order)
    {
        $order = $this->tiko->syncOrder($order);

        return response()
            ->view('payment.return', [
                'order' => $order,
                'url' => route('order.show', $order->number),
            ])
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * Tiko'nun sunucudan sunucuya bildirimi (JSON).
     *
     * Belgeye göre HTTP 200 dışında bir yanıtta Tiko gün içinde birkaç kez
     * daha deniyor; işleyemediğimizde bilerek 400 dönüyoruz ki yeniden
     * denesin.
     */
    public function callback(Request $request)
    {
        $order = $this->tiko->handleCallback($request->json()->all() ?: $request->all());

        return response($order ? 'OK' : 'FAIL', $order ? 200 : 400);
    }

    private function owns(Request $request, Order $order): bool
    {
        return in_array($order->number, (array) $request->session()->get('my_orders', []), true);
    }
}
