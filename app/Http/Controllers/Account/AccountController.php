<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('account.index', [
            'orders' => $user->orders()->with('items')->limit(3)->get(),
            'orderCount' => $user->orders()->count(),
            'addressCount' => $user->addresses()->count(),
            'favoriteCount' => $user->favorites()->count(),
            'openOrder' => $user->orders()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->latest('id')
                ->first(),
        ]);
    }

    public function profile()
    {
        return view('account.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['required', 'string', 'max:40'],
        ], [], [
            'name' => 'ad soyad',
            'email' => 'e-posta adresi',
            'phone' => 'telefon',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
        ]);

        return back()->with('success', 'Bilgileriniz güncellendi.');
    }

    /**
     * KVKK — hesaptaki tüm kişisel veriyi tek dosyada indir.
     */
    public function downloadData()
    {
        $user = Auth::user();

        $payload = [
            'olusturma_tarihi' => now()->toIso8601String(),
            'hesap' => [
                'ad_soyad' => $user->name,
                'eposta' => $user->email,
                'telefon' => $user->phone,
                'kayit_tarihi' => $user->created_at?->toIso8601String(),
            ],
            'adresler' => $user->addresses()->with('zone')->get()->map(fn ($a) => [
                'baslik' => $a->title,
                'alici' => $a->recipient_name,
                'telefon' => $a->recipient_phone,
                'bolge' => $a->zone?->name,
                'adres' => $a->address,
                'varsayilan' => $a->is_default,
            ]),
            'favoriler' => $user->favorites()->pluck('products.name'),
            'siparisler' => $user->orders()->with('items')->get()->map(fn ($o) => [
                'numara' => $o->number,
                'tarih' => $o->created_at?->toIso8601String(),
                'durum' => $o->status_label,
                'odeme' => $o->payment_method_label,
                'alici' => $o->recipient_name,
                'alici_telefon' => $o->recipient_phone,
                'bolge' => $o->delivery_zone_name,
                'adres' => $o->delivery_address,
                'teslim_tarihi' => $o->delivery_date?->toDateString(),
                'kart_notu' => $o->card_message,
                'toplam' => (float) $o->total,
                'urunler' => $o->items->map(fn ($i) => [
                    'ad' => $i->name,
                    'boy' => $i->variant_name,
                    'adet' => $i->quantity,
                    'tutar' => (float) $i->line_total,
                ]),
            ]),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(
            fn () => print ($json),
            'ayparcasi-verilerim-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /**
     * KVKK — hesabı sil.
     *
     * Siparişler muhasebe kaydı olduğu için silinmez; kişisel alanları
     * anonimleştirilir. Hesap, adresler ve favoriler tamamen silinir.
     */
    public function destroyAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => ['required'],
            'onay' => ['accepted'],
        ], [], [
            'password' => 'parola',
            'onay' => 'silme onayı',
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Parolanız hatalı.',
            ]);
        }

        $userId = $user->getKey();

        // Önce oturumu kapat: Auth::logout() hatırlama jetonunu tazelemek için
        // modeli kaydedebiliyor; silinmiş kaydı geri yazmasın diye sıra bu.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($userId) {
            Order::where('user_id', $userId)->update([
                'user_id' => null,
                'customer_name' => 'Silinmiş müşteri',
                'customer_phone' => '—',
                'customer_email' => null,
                'recipient_name' => 'Silinmiş kayıt',
                'recipient_phone' => null,
                'delivery_address' => '—',
                'card_message' => null,
                'card_sender' => null,
                'note' => null,
                'ip' => null,
            ]);

            Address::where('user_id', $userId)->delete();
            DB::table('favorites')->where('user_id', $userId)->delete();
            User::whereKey($userId)->delete();
        });

        return redirect()->route('home')->with(
            'success',
            'Hesabınız silindi. Geçmiş siparişleriniz muhasebe kaydı olarak anonim şekilde saklanır.'
        );
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'mevcut parola',
            'password' => 'yeni parola',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mevcut parolanız hatalı.',
            ]);
        }

        $user->update(['password' => $data['password']]);

        return back()->with('success', 'Parolanız güncellendi.');
    }
}
