<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('account.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'e-posta adresi',
            'password' => 'parola',
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'E-posta veya parola hatalı.',
            ]);
        }

        $request->session()->regenerate();

        // Ekip üyesi vitrin hesabı sayfasına değil panele gitsin
        if (! Auth::user()->isCustomer()) {
            return redirect('/admin');
        }

        return redirect()
            ->intended(route('account.index'))
            ->with('success', 'Hoş geldiniz, '.Auth::user()->first_name.'.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Çıkış yapıldı.');
    }
}
