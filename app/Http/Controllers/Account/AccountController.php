<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
