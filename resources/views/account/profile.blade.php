@php $user = auth()->user(); @endphp

<x-layouts.account title="Bilgilerim" heading="Bilgilerim">

    <section class="acc-box">
        <h2>Hesap bilgileri</h2>

        <form method="POST" action="{{ route('account.profile.update') }}" class="address-form">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Ad soyad</label>
                <input class="input @error('name') is-error @enderror" type="text" name="name" id="name"
                       value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name')<span class="field__error">{{ $message }}</span>@enderror
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="email">E-posta</label>
                    <input class="input @error('email') is-error @enderror" type="email" name="email" id="email"
                           value="{{ old('email', $user->email) }}" required autocomplete="email">
                    @error('email')<span class="field__error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label for="phone">Telefon</label>
                    <input class="input @error('phone') is-error @enderror" type="tel" name="phone" id="phone"
                           value="{{ old('phone', $user->phone) }}" required autocomplete="tel">
                    @error('phone')<span class="field__error">{{ $message }}</span>@enderror
                </div>
            </div>

            <button class="btn btn--rect" type="submit">Bilgileri kaydet</button>
        </form>
    </section>

    <section class="acc-box" style="margin-top:1.75rem">
        <h2>Parola değiştir</h2>

        <form method="POST" action="{{ route('account.password.update') }}" class="address-form">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="current_password">Mevcut parolanız</label>
                <input class="input @error('current_password') is-error @enderror" type="password"
                       name="current_password" id="current_password" required autocomplete="current-password">
                @error('current_password')<span class="field__error">{{ $message }}</span>@enderror
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="new_password">Yeni parola</label>
                    <input class="input @error('password') is-error @enderror" type="password"
                           name="password" id="new_password" required autocomplete="new-password">
                    <span class="field__hint">En az 8 karakter.</span>
                    @error('password')<span class="field__error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Yeni parola tekrar</label>
                    <input class="input" type="password" name="password_confirmation" id="password_confirmation"
                           required autocomplete="new-password">
                </div>
            </div>

            <button class="btn btn--rect" type="submit">Parolayı güncelle</button>
        </form>
    </section>

    <section class="acc-box" style="margin-top:1.75rem">
        <h2>Verileriniz</h2>

        <p class="muted" style="font-size:.92rem">
            Hesabınızda tuttuğumuz her şeyi tek dosyada indirebilir ya da hesabınızı tamamen silebilirsiniz.
        </p>

        <a class="btn btn--rect btn--ghost" style="margin-top:1rem" href="{{ route('account.data.download') }}">
            <x-ay-icon name="arrow-down" /> Verilerimi indir
        </a>

        <details class="danger-zone">
            <summary>Hesabımı sil</summary>

            <p class="muted" style="font-size:.9rem;margin-top:.75rem">
                Hesabınız, adresleriniz ve favorileriniz kalıcı olarak silinir.
                Geçmiş siparişleriniz muhasebe kaydı olduğu için silinmez;
                adınız, telefonunuz ve adresiniz o kayıtlardan çıkarılır.
                <strong>Bu işlem geri alınamaz.</strong>
            </p>

            <form method="POST" action="{{ route('account.destroy') }}" class="address-form"
                  onsubmit="return confirm('Hesabınız kalıcı olarak silinecek. Emin misiniz?')">
                @csrf
                @method('DELETE')

                <div class="field" style="max-width:22rem">
                    <label for="delete_password">Parolanızı yazın</label>
                    <input class="input @error('password') is-error @enderror" type="password"
                           name="password" id="delete_password" required autocomplete="current-password">
                    @error('password')<span class="field__error">{{ $message }}</span>@enderror
                </div>

                <label class="choice choice--check">
                    <input type="checkbox" name="onay" value="1" required>
                    <span class="choice__dot" aria-hidden="true"></span>
                    <span class="choice__text">
                        <span class="choice__title">Anladım, hesabım kalıcı olarak silinsin</span>
                    </span>
                </label>
                @error('onay')<span class="field__error">{{ $message }}</span>@enderror

                <button class="btn btn--rect btn--danger" type="submit">Hesabımı sil</button>
            </form>
        </details>
    </section>
</x-layouts.account>
