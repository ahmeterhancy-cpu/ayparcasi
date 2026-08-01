@props(['class' => ''])

{{-- Ay Parçası işareti: mozaik halka + güneş/ay yüzü.
     Logonun sadeleştirilmiş, tek renkli-uyumlu vektör karşılığı. --}}
<svg class="{{ $class }}" viewBox="0 0 64 64" fill="none" aria-hidden="true" focusable="false">
    {{-- Güneş ışınları --}}
    <g stroke="var(--sun)" stroke-width="2.4" stroke-linecap="round">
        @for ($i = 0; $i < 12; $i++)
            <line
                x1="32" y1="4.5" x2="32" y2="10"
                transform="rotate({{ $i * 30 }} 32 32)"
            />
        @endfor
    </g>

    {{-- Mozaik halka --}}
    <circle cx="32" cy="32" r="19.5" stroke="var(--turq)" stroke-width="5" stroke-dasharray="3.2 3.2" />

    {{-- Ay yarısı (sarı) --}}
    <path d="M32 16a16 16 0 0 0 0 32z" fill="var(--sun)" />
    {{-- Güneş yarısı (mavi) --}}
    <path d="M32 16a16 16 0 0 1 0 32z" fill="var(--sea)" />

    {{-- Profil çizgisi --}}
    <path d="M32 16v32" stroke="var(--ink)" stroke-width="1.6" />

    {{-- Gözler --}}
    <circle cx="25.5" cy="27" r="1.9" fill="var(--ink)" />
    <circle cx="38.5" cy="27" r="1.9" fill="var(--paper)" />

    {{-- Hilal --}}
    <path
        d="M46 12a24 24 0 0 1 0 40 20 20 0 0 0 0-40z"
        fill="var(--sea)"
        opacity=".9"
    />
</svg>
