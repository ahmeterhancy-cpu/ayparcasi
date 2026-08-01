<x-layouts.auth title="Parolamı unuttum" heading="Parolanızı sıfırlayalım"
                lead="E-posta adresinizi yazın, sıfırlama bağlantısını gönderelim.">

    <form method="POST" action="{{ route('password.email') }}" class="auth__form">
        @csrf

        <div class="field">
            <label for="email">E-posta adresiniz</label>
            <input class="input @error('email') is-error @enderror" type="email" name="email" id="email"
                   value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')<span class="field__error">{{ $message }}</span>@enderror
        </div>

        <button class="btn btn--rect btn--block btn--lg" type="submit">Bağlantıyı gönder</button>
    </form>

    <p class="auth__foot">
        <a class="link-u" href="{{ route('login') }}">Girişe dön</a>
    </p>

    <p class="auth__note">
        Bağlantı gelmezse gereksiz/spam klasörünüze bakın ya da WhatsApp'tan yazın.
    </p>
</x-layouts.auth>
