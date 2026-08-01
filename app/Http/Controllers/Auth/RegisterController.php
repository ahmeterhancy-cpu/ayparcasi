<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('account.index');
        }

        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'kvkk' => ['accepted'],
        ], [
            'email.unique' => 'Bu e-posta ile zaten bir hesap var. Giriş yapmayı deneyin.',
        ], [
            'name' => 'ad soyad',
            'email' => 'e-posta adresi',
            'phone' => 'telefon',
            'password' => 'parola',
            'kvkk' => 'aydınlatma metni onayı',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
            'password' => $data['password'],
            // Rol ASLA formdan gelmez — yeni kayıtlar her zaman müşteridir.
            'role' => 'customer',
        ]);

        event(new Registered($user));

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('account.index')
            ->with('success', 'Hesabınız hazır. Hoş geldiniz, '.$user->first_name.'.');
    }
}
