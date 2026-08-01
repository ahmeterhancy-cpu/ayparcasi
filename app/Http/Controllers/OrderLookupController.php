<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Hesabı olmayan müşteri, sipariş numarası + telefon (ya da e-posta) ile
 * siparişini görebilsin. Doğrulanınca sipariş oturuma eklenir.
 */
class OrderLookupController extends Controller
{
    public function show()
    {
        return view('order-lookup');
    }

    public function find(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:190'],
        ], [], [
            'number' => 'sipariş numarası',
            'contact' => 'telefon veya e-posta',
        ]);

        $order = Order::whereRaw('UPPER(number) = ?', [mb_strtoupper(trim($data['number']))])->first();

        if (! $order || ! $this->contactMatches($order, $data['contact'])) {
            // Hangi bilginin yanlış olduğunu söylemiyoruz — numara denemesini zorlaştırır
            throw ValidationException::withMessages([
                'number' => 'Bu bilgilerle bir sipariş bulamadık. Numarayı ve telefonu kontrol edin.',
            ]);
        }

        // Oturuma ekle ki sipariş sayfası açılabilsin
        $orders = (array) $request->session()->get('my_orders', []);
        $orders[] = $order->number;
        $request->session()->put('my_orders', array_slice(array_unique($orders), -20));

        return redirect()->route('order.show', $order->number);
    }

    private function contactMatches(Order $order, string $contact): bool
    {
        $contact = trim($contact);

        if (str_contains($contact, '@')) {
            return $order->customer_email
                && hash_equals(mb_strtolower($order->customer_email), mb_strtolower($contact));
        }

        // Telefonu biçimden bağımsız karşılaştır: yalnız rakamları al ve
        // son 9 haneye bak. Böylece "0533 111 22 33" ile "+90 533 111 22 33"
        // aynı sayılır, baştaki 0 / +90 farkı sorun olmaz.
        $tail = function (?string $v): ?string {
            $digits = preg_replace('/\D+/', '', (string) $v);

            return strlen($digits) >= 9 ? substr($digits, -9) : null;
        };

        $given = $tail($contact);

        if ($given === null) {
            return false;
        }

        foreach ([$order->customer_phone, $order->recipient_phone] as $stored) {
            if ($tail($stored) === $given) {
                return true;
            }
        }

        return false;
    }
}
