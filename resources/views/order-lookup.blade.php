<x-layouts.auth title="Sipariş sorgula" heading="Siparişinizi bulalım"
                lead="Hesap açmadan sipariş verdiyseniz, sipariş numaranız ve telefonunuzla durumunu görebilirsiniz.">

    <form method="POST" action="{{ route('order.lookup.find') }}" class="auth__form">
        @csrf

        <div class="field">
            <label for="number">Sipariş numarası</label>
            <input class="input @error('number') is-error @enderror" type="text" name="number" id="number"
                   value="{{ old('number') }}" required autofocus placeholder="AP260801001"
                   style="text-transform:uppercase">
            @error('number')<span class="field__error">{{ $message }}</span>@enderror
            <span class="field__hint">Sipariş onay sayfasında ve WhatsApp mesajında yazıyor.</span>
        </div>

        <div class="field">
            <label for="contact">Telefon veya e-posta</label>
            <input class="input @error('contact') is-error @enderror" type="text" name="contact" id="contact"
                   value="{{ old('contact') }}" required placeholder="0533 000 00 00">
            @error('contact')<span class="field__error">{{ $message }}</span>@enderror
            <span class="field__hint">Siparişte verdiğiniz telefon ya da e-posta.</span>
        </div>

        <button class="btn btn--rect btn--block btn--lg" type="submit">Siparişi göster</button>
    </form>

    <p class="auth__foot">
        Hesabınız varsa <a class="link-u" href="{{ route('login') }}">giriş yapın</a> — tüm siparişleriniz bir arada.
    </p>

    <p class="auth__note">
        Bulamadıysanız WhatsApp'tan yazın:
        <a class="link-u" href="{{ wa_link('Merhaba, siparişimi sorgulamak istiyorum.') }}" target="_blank" rel="noopener">
            {{ setting('phone', 'WhatsApp') }}
        </a>
    </p>
</x-layouts.auth>
