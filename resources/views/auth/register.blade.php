<x-layouts.auth title="Hesap oluştur" heading="Hesabınızı oluşturun"
                lead="Siparişleriniz, adresleriniz ve favorileriniz tek yerde toplansın.">

    <form method="POST" action="{{ route('register') }}" class="auth__form">
        @csrf

        <div class="field">
            <label for="name">Ad soyad</label>
            <input class="input @error('name') is-error @enderror" type="text" name="name" id="name"
                   value="{{ old('name') }}" required autocomplete="name" autofocus>
            @error('name')<span class="field__error">{{ $message }}</span>@enderror
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="email">E-posta</label>
                <input class="input @error('email') is-error @enderror" type="email" name="email" id="email"
                       value="{{ old('email') }}" required autocomplete="email">
                @error('email')<span class="field__error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="phone">Telefon</label>
                <input class="input @error('phone') is-error @enderror" type="tel" name="phone" id="phone"
                       value="{{ old('phone') }}" required autocomplete="tel" placeholder="0533 000 00 00">
                @error('phone')<span class="field__error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="password">Parola</label>
                <input class="input @error('password') is-error @enderror" type="password" name="password" id="password"
                       required autocomplete="new-password">
                <span class="field__hint">En az 8 karakter.</span>
                @error('password')<span class="field__error">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Parola tekrar</label>
                <input class="input" type="password" name="password_confirmation" id="password_confirmation"
                       required autocomplete="new-password">
            </div>
        </div>

        <label class="choice choice--check">
            <input type="checkbox" name="kvkk" value="1" required @checked(old('kvkk'))>
            <span class="choice__dot" aria-hidden="true"></span>
            <span class="choice__text">
                <span class="choice__title">Aydınlatma metnini okudum, onaylıyorum</span>
                <span class="choice__meta">Bilgileriniz yalnızca siparişleriniz için kullanılır.</span>
            </span>
        </label>
        @error('kvkk')<span class="field__error">{{ $message }}</span>@enderror

        <button class="btn btn--rect btn--block btn--lg" type="submit">Hesabı oluştur</button>
    </form>

    <p class="auth__foot">
        Zaten hesabınız var mı? <a class="link-u" href="{{ route('login') }}">Giriş yapın</a>
    </p>
</x-layouts.auth>
