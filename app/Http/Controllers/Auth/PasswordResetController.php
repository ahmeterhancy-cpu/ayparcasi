<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']], [], ['email' => 'e-posta adresi']);

        Password::sendResetLink($request->only('email'));

        // Hangi e-postanın kayıtlı olduğunu sızdırmamak için sonuç hep aynı
        return back()->with('success', 'Bu adres kayıtlıysa parola sıfırlama bağlantısını gönderdik.');
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.reset', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [], [
            'email' => 'e-posta adresi',
            'password' => 'parola',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Bağlantı geçersiz ya da süresi dolmuş. Yeniden deneyin.');
        }

        return redirect()->route('login')->with('success', 'Parolanız güncellendi, giriş yapabilirsiniz.');
    }
}
