{{-- Vitrin kapalıyken panelin her sayfasında duran uyarı şeridi.

     Panelde Tailwind yardımcı sınıfları çalışmaz (bkz. filament/theme),
     bu yüzden düz satır içi stil. Mercan zemin üzerinde yazı rengi elle
     #ffffff verilir — sınıfa bırakılırsa tema eşlemesi bozabiliyor. --}}
@if (setting('maintenance_enabled'))
    <div style="
        display:flex; flex-wrap:wrap; align-items:center; gap:.55rem 1rem;
        padding:.7rem 1.25rem; background:#db4a32; color:#ffffff;
        font-size:.875rem; font-weight:600; line-height:1.4;
    ">
        <span style="color:#ffffff">
            Site şu anda <strong style="color:#ffffff">yapım aşamasında</strong> — ziyaretçiler vitrini göremiyor.
        </span>

        <a
            href="{{ \App\Filament\Pages\SiteSettings::getUrl() }}"
            style="
                color:#ffffff; text-decoration:underline; text-underline-offset:3px;
                margin-inline-start:auto; white-space:nowrap;
            "
        >
            Ayarı aç
        </a>
    </div>
@endif
