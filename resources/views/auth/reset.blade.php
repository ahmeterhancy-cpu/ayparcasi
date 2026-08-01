<x-layouts.auth title="Yeni parola" heading="Yeni parolanızı belirleyin">

    <form method="POST" action="{{ route('password.update') }}" class="auth__form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">E-posta adresiniz</label>
            <input class="input @error('email') is-error @enderror" type="email" name="email" id="email"
                   value="{{ old('email', $email) }}" required autocomplete="email">
            @error('email')<span class="field__error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label for="password">Yeni parola</label>
            <input class="input @error('password') is-error @enderror" type="password" name="password" id="password"
                   required autocomplete="new-password" autofocus>
            <span class="field__hint">En az 8 karakter.</span>
            @error('password')<span class="field__error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Yeni parola tekrar</label>
            <input class="input" type="password" name="password_confirmation" id="password_confirmation"
                   required autocomplete="new-password">
        </div>

        <button class="btn btn--rect btn--block btn--lg" type="submit">Parolayı güncelle</button>
    </form>
</x-layouts.auth>
