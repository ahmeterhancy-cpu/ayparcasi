{{-- Ay Parçası — yönetim paneli marka katmanı.

     Filament'in derlenmiş CSS'i yalnızca kendi `fi-` sınıflarını içerir;
     Tailwind yardımcı sınıfları burada ÇALIŞMAZ. Bu yüzden düz CSS yazılır.
     Panel yapılandırmasında HEAD_END kancasıyla basılır. --}}

{{ Vite::fonts() }}

<style>
    :root {
        /* Logo bileşeninin (components/logo.blade.php) beklediği adlar */
        --ink: #0e2c34;
        --sea: #16697f;
        --turq: #4cbfc4;
        --sun: #f4b02a;
        --paper: #fcf8f2;

        /* Panele özel, yalnız burada kullanılanlar.
           Vitrindeki --ink-3 (#5d7c83) kart zemininde AA'yı geçmiyordu (4.42),
           soluk metin için bir tık koyusu kullanılıyor. */
        --ay-ink-3: #4f6b73;
        --ay-turq-3: #d6f0ee;
        --ay-sand: #efe4d3;
        --ay-line: rgba(14, 44, 52, 0.11);
    }

    /* Başlıklar vitrindeki display fontuyla aynı olsun */
    .fi-simple-header-heading,
    .fi-header-heading,
    .fi-modal-heading {
        font-family: 'Fraunces', 'Iowan Old Style', Georgia, serif;
        letter-spacing: -0.01em;
    }

    /* ----------------------------------------------------------------------
       Kenar çubuğu — Filament varsayılanı fazla seyrek duruyordu.
       Genişlik CSS'ten DEĞİL, panel yapılandırmasındaki sidebarWidth()'ten
       geliyor (--sidebar-width); buradakiler yalnız boşluklar.
       Filament varsayılanları: gruplar arası 1.75rem, dikey iç boşluk 2rem,
       yatay 1.5rem, öğe iç boşluğu 0.5rem.
       Bu blok HEAD_END'de basıldığı için Filament'in tabakasından SONRA gelir
       — eşit özgüllükte sonraki kazanır, !important gerekmiyor.
       ---------------------------------------------------------------------- */

    .fi-sidebar-nav {
        row-gap: 1.15rem;
        padding-block: 1.1rem;
        padding-inline: 0.8rem;
    }

    .fi-sidebar-item-btn {
        padding-block: 0.4rem;
        padding-inline: 0.55rem;
    }

    /* Grup başlığı (Satış / Katalog / Teslimat) 1.5rem satır yüksekliğiyle
       geliyordu; başlık ile ilk öğe arasını gereksiz açıyordu. */
    .fi-sidebar-group-label {
        line-height: 1.3rem;
    }

    /* ----------------------------------------------------------------------
       Giriş / parola ekranları (fi-simple-*)
       ---------------------------------------------------------------------- */

    .fi-simple-layout {
        min-height: 100vh;
        background-color: var(--paper);
        background-image:
            /* köşelerde yumuşak marka ışıması */
            radial-gradient(60rem 40rem at 12% -10%, rgba(76, 191, 196, 0.24), transparent 62%),
            radial-gradient(52rem 38rem at 96% 108%, rgba(244, 176, 42, 0.22), transparent 64%),
            /* mozaik izi — desen değil, yalnız eğik saç teli dokusu */
            repeating-linear-gradient(
                135deg,
                rgba(22, 105, 127, 0.05) 0 1px,
                transparent 1px 24px
            );
        background-attachment: fixed;
    }

    /* Kart */
    .fi-simple-main {
        background-color: #fffdfa !important;
        border: 1px solid var(--ay-line);
        border-radius: 18px !important;
        padding: clamp(1.75rem, 4vw, 2.75rem) !important;
        box-shadow:
            0 1px 2px rgba(14, 44, 52, 0.05),
            0 34px 70px -44px rgba(14, 44, 52, 0.6);
    }

    /* Logo — vitrindeki kemer (arch) formunda madalyon */
    .fi-simple-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    /* --- Marka görseli ------------------------------------------------- */

    /* Varsayılan (kenar çubuğu, üst çubuk): yatay kilit.
       Filament yüksekliği .fi-logo'ya satır içi stille basar. */
    .fi-logo .ay-brand-lockup {
        height: 100%;
        width: auto;
    }

    .fi-logo .ay-brand-mark {
        display: none;
    }

    /* Giriş / parola ekranları: yalnız işaret, büyük ve ortalanmış */
    .fi-simple-header .fi-logo {
        height: auto !important;
        margin-bottom: 0.75rem;
    }

    .fi-simple-header .fi-logo .ay-brand-lockup {
        display: none;
    }

    .fi-simple-header .fi-logo .ay-brand-mark {
        display: block;
        width: clamp(7rem, 26vw, 8.75rem);
        height: auto;
        margin-inline: auto;
        filter: drop-shadow(0 12px 22px rgba(14, 44, 52, 0.22));
    }

    .fi-simple-header-heading {
        color: var(--ink);
        font-size: clamp(1.55rem, 3vw, 1.95rem);
        line-height: 1.2;
    }

    .fi-simple-header-subheading {
        color: var(--ay-ink-3);
        font-size: 0.92rem;
        max-width: 26rem;
        margin-inline: auto;
    }

    /* Kartın altındaki vitrine dönüş bağlantısı */
    .ay-auth-foot {
        margin-top: 1.5rem;
        text-align: center;
        font-size: 0.85rem;
        color: var(--ay-ink-3);
    }

    .ay-auth-foot a {
        color: var(--sea);
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .ay-auth-foot a:hover {
        color: var(--ink);
    }

    /* ----------------------------------------------------------------------
       Koyu tema karşılıkları
       ---------------------------------------------------------------------- */

    .dark .fi-simple-layout {
        background-color: #0b1f25;
        background-image:
            radial-gradient(60rem 40rem at 12% -10%, rgba(76, 191, 196, 0.16), transparent 62%),
            radial-gradient(52rem 38rem at 96% 108%, rgba(244, 176, 42, 0.1), transparent 64%),
            repeating-linear-gradient(
                135deg,
                rgba(214, 240, 238, 0.035) 0 1px,
                transparent 1px 24px
            );
    }

    .dark .fi-simple-main {
        background-color: #10292f !important;
        border-color: rgba(214, 240, 238, 0.12);
    }

    /* Kilitteki "AY PARÇASI" yazısı neredeyse siyah — koyu zeminde kaybolur.
       Marka renklerini bozmamak için filtre yerine açık bir plaka konur.
       İşaretin kendisi renkli ve koyu zeminde zaten okunur; ona dokunulmaz. */
    .dark .fi-logo {
        background: #fdfaf4;
        padding: 0.35rem 0.7rem;
        border-radius: 10px;
        box-sizing: content-box;
    }

    /* Giriş ekranında kilit değil işaret görünür; plakaya gerek yok. */
    .dark .fi-simple-header .fi-logo {
        background: none;
        padding: 0;
    }

    .dark .fi-simple-header .fi-logo .ay-brand-mark {
        filter: drop-shadow(0 12px 26px rgba(0, 0, 0, 0.55));
    }

    .dark .fi-simple-header-heading {
        color: #eef6f5;
    }

    .dark .fi-simple-header-subheading,
    .dark .ay-auth-foot {
        color: #9db6bb;
    }

    .dark .ay-auth-foot a {
        color: var(--turq);
    }

    .dark .ay-auth-foot a:hover {
        color: #ffffff;
    }
</style>
