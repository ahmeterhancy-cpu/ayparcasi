<?php

namespace App\Http\Controllers;

use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use App\Services\Payments\TikoGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(private readonly Cart $cart) {}

    public function index()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('checkout', [
            'lines' => $this->cart->lines(),
            'summary' => $this->cart->summary(),
            'zones' => DeliveryZone::active()->orderBy('position')->get(),
            'slots' => DeliverySlot::active()->orderBy('position')->get(),
            'tikoEnabled' => app(TikoGateway::class)->isConfigured(),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:190'],

            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['nullable', 'string', 'max:40'],
            'delivery_zone_id' => ['required', Rule::exists('delivery_zones', 'id')->where('is_active', true)],
            'delivery_address' => ['required', 'string', 'max:600'],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_slot' => ['nullable', 'string', 'max:60'],

            'card_message' => ['nullable', 'string', 'max:400'],
            'card_sender' => ['nullable', 'string', 'max:120'],
            'hide_sender' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:600'],

            'payment_method' => ['required', Rule::in(array_keys(Order::PAYMENT_METHODS))],
            'kvkk' => ['accepted'],
        ], [], [
            'customer_name' => 'adınız',
            'customer_phone' => 'telefonunuz',
            'recipient_name' => 'alıcının adı',
            'delivery_zone_id' => 'teslimat bölgesi',
            'delivery_address' => 'teslimat adresi',
            'delivery_date' => 'teslimat tarihi',
            'kvkk' => 'aydınlatma metni onayı',
        ]);

        $zone = DeliveryZone::findOrFail($data['delivery_zone_id']);

        if (! $zone->same_day && $data['delivery_date'] === now()->toDateString()) {
            throw ValidationException::withMessages([
                'delivery_date' => $zone->name.' bölgesine aynı gün teslimat yapamıyoruz. Lütfen ileri bir tarih seçin.',
            ]);
        }

        if ($data['payment_method'] === 'tiko' && ! app(TikoGateway::class)->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Kart ile ödeme şu an kapalı. Kapıda ödeme, havale veya WhatsApp ile sipariş verebilirsiniz.',
            ]);
        }

        $lines = $this->cart->lines();
        $coupon = $this->cart->coupon();
        $summary = $this->cart->summary($zone);

        $order = DB::transaction(function () use ($data, $zone, $lines, $coupon, $summary, $request) {
            // Stok kontrolü ve düşümü — kilitli satırlarla
            foreach ($lines as $line) {
                /** @var Product $product */
                $product = Product::whereKey($line['product']->id)->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => $line['product']->name.' artık satışta değil. Lütfen sepetinizi güncelleyin.',
                    ]);
                }

                if (! $product->track_stock) {
                    continue;
                }

                if ($line['variant']) {
                    $variant = $product->variants()->whereKey($line['variant']->id)->lockForUpdate()->first();

                    if (! $variant || $variant->stock < $line['quantity']) {
                        throw ValidationException::withMessages([
                            'cart' => $product->name.' ('.$line['variant']->name.') için yeterli stok yok.',
                        ]);
                    }

                    $variant->decrement('stock', $line['quantity']);
                } else {
                    if ($product->stock < $line['quantity']) {
                        throw ValidationException::withMessages([
                            'cart' => $product->name.' için yeterli stok yok.',
                        ]);
                    }

                    $product->decrement('stock', $line['quantity']);
                }
            }

            $order = Order::create([
                'number' => Order::nextNumber(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],

                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,

                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'delivery_zone_id' => $zone->id,
                'delivery_zone_name' => $zone->name,
                'delivery_address' => $data['delivery_address'],
                'delivery_date' => $data['delivery_date'],
                'delivery_slot' => $data['delivery_slot'] ?? null,

                'card_message' => $data['card_message'] ?? null,
                'card_sender' => $data['card_sender'] ?? null,
                'hide_sender' => (bool) ($data['hide_sender'] ?? false),
                'note' => $data['note'] ?? null,

                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'delivery_fee' => $summary['delivery_fee'],
                'total' => $summary['total'],

                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,

                'ip' => $request->ip(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'name' => $line['product']->name,
                    'variant_name' => $line['variant']?->name,
                    'image' => $line['product']->hero_image,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                    'addons' => $line['addons']->isNotEmpty() ? $line['addons']->all() : null,
                ]);
            }

            $coupon?->increment('used_count');

            return $order;
        });

        $this->cart->clear();
        $this->rememberOrder($order);

        if ($order->payment_method === 'tiko') {
            return redirect()->route('payment.redirect', $order->number);
        }

        if ($order->payment_method === 'whatsapp') {
            return redirect()->route('order.show', $order->number)->with('open_whatsapp', true);
        }

        return redirect()->route('order.show', $order->number);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($this->canView($order), 404);

        $order->load('items');

        return view('order', [
            'order' => $order,
            'whatsappUrl' => wa_link($this->orderMessage($order)),
            'openWhatsapp' => (bool) $request->session()->get('open_whatsapp'),
        ]);
    }

    /** Misafir müşteri yalnızca kendi oluşturduğu siparişi görebilir. */
    private function canView(Order $order): bool
    {
        return in_array($order->number, (array) session('my_orders', []), true);
    }

    private function rememberOrder(Order $order): void
    {
        $orders = (array) session('my_orders', []);
        $orders[] = $order->number;
        session(['my_orders' => array_slice(array_unique($orders), -20)]);
    }

    /** WhatsApp'a gönderilecek okunabilir sipariş özeti. */
    private function orderMessage(Order $order): string
    {
        $lines = ['Merhaba, ayparcasicicekci.com üzerinden sipariş oluşturdum.', ''];
        $lines[] = 'Sipariş no: '.$order->number;

        foreach ($order->items as $item) {
            $lines[] = '• '.$item->quantity.' x '.$item->name
                .($item->variant_name ? ' ('.$item->variant_name.')' : '')
                .' — '.money($item->line_total);
        }

        $lines[] = '';
        $lines[] = 'Ara toplam: '.money($order->subtotal);

        if ((float) $order->discount > 0) {
            $lines[] = 'İndirim: -'.money($order->discount);
        }

        $lines[] = 'Teslimat: '.money($order->delivery_fee);
        $lines[] = 'Toplam: '.money($order->total);
        $lines[] = '';
        $lines[] = 'Alıcı: '.$order->recipient_name.($order->recipient_phone ? ' ('.$order->recipient_phone.')' : '');
        $lines[] = 'Bölge: '.$order->delivery_zone_name;
        $lines[] = 'Adres: '.$order->delivery_address;
        $lines[] = 'Tarih: '.$order->delivery_date?->format('d.m.Y').($order->delivery_slot ? ' / '.$order->delivery_slot : '');

        if ($order->card_message) {
            $lines[] = 'Kart notu: '.$order->card_message;
        }

        return implode("\n", $lines);
    }
}
