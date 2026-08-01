<x-layouts.auth title="Giriş yap" heading="Tekrar hoş geldiniz"
                lead="Siparişlerinizi takip edin, adreslerinizi ve favorilerinizi bir arada tutun.">

    <form method="POST" action="{{ route('login') }}" class="auth__form">
        @csrf

        <div class="field">
            <label for="email">E-posta adresiniz</label>
            <input class="input @error('email') is-error @enderror" type="email" name="email" id="email"
                   value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')<span class="field__error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label for="password">Parolanız</label>
            <input class="input @error('password') is-error @enderror" type="password" name="password" id="password"
                   required autocomplete="current-password">
            @error('password')<span class="field__error">{{ $message }}</span>@enderror
        </div>

        <div class="auth__row">
            <label class="choice choice--check auth__remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span class="choice__dot" aria-hidden="true"></span>
                <span class="choice__text"><span class="choice__title">Beni hatırla</span></span>
            </label>

            <a class="link-u" href="{{ route('password.request') }}">Parolamı unuttum</a>
        </div>

        <button class="btn btn--rect btn--block btn--lg" type="submit">Giriş yap</button>
    </form>

    <p class="auth__foot">
        Hesabınız yok mu? <a class="link-u" href="{{ route('register') }}">Hemen oluşturun</a>
    </p>

    <p class="auth__note">
        Hesap açmadan da sipariş verebilirsiniz — kasada misafir olarak devam edin.
    </p>
</x-layouts.auth>
