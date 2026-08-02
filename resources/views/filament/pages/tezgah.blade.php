{{-- Tezgâh modu — telefonda ürün girme ekranı.
     Panelde Tailwind sınıfları çalışmaz (bkz. filament/theme), stiller satır içi. --}}
<x-filament-panels::page>
    <form wire:submit.prevent="save" class="fi-form">
        {{ $this->form }}

        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:.75rem; margin-top:1.5rem">
            <x-filament::button type="submit" size="lg" wire:loading.attr="disabled">
                Kaydet ve yenisini ekle
            </x-filament::button>

            <span class="fi-color-gray" style="font-size:.875rem" wire:loading wire:target="save">
                Kaydediliyor…
            </span>
        </div>
    </form>

    @if ($eklenenler)
        <div style="margin-top:2rem">
            <h3 style="font-size:.95rem; font-weight:600; margin-bottom:.75rem">
                Bu oturumda eklenenler ({{ count($eklenenler) }})
            </h3>

            <ul style="display:grid; gap:.5rem; list-style:none; padding:0; margin:0">
                @foreach ($eklenenler as $urun)
                    <li>
                        <a
                            href="{{ $urun['url'] }}"
                            style="
                                display:flex; align-items:center; gap:.75rem;
                                padding:.6rem .75rem; border:1px solid var(--ay-line, rgba(14,44,52,.11));
                                border-radius:.6rem; text-decoration:none;
                            "
                        >
                            @if ($urun['image'])
                                <img src="{{ $urun['image'] }}" alt="" width="40" height="40"
                                     style="width:2.5rem; height:2.5rem; border-radius:.4rem; object-fit:cover; flex:none">
                            @else
                                <span style="width:2.5rem; height:2.5rem; border-radius:.4rem; flex:none; background:var(--ay-sand, #efe4d3)"></span>
                            @endif

                            <span style="flex:1 1 auto; min-width:0">
                                <span style="display:block; font-weight:600">{{ $urun['name'] }}</span>
                                <span class="fi-color-gray" style="font-size:.8rem">
                                    {{ $urun['price'] }} · {{ $urun['active'] ? 'Yayında' : 'Taslak' }}
                                </span>
                            </span>

                            <span class="fi-color-gray" style="font-size:.8rem; white-space:nowrap">Düzenle →</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-panels::page>
