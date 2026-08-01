<x-layouts.account title="Adreslerim" heading="Adreslerim"
                   lead="Kayıtlı adresleriniz kasada tek tıkla seçilir; her seferinde yeniden yazmazsınız.">

    @if ($errors->any())
        <div class="alert">
            Formda eksik alanlar var:
            <ul>@foreach ($errors->all() as $e)<li>· {{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($addresses->isEmpty())
        <div class="acc-empty">
            <x-ay-icon name="pin" style="width:38px;height:38px;color:var(--turq)" />
            <h3>Kayıtlı adresiniz yok</h3>
            <p class="muted">Aşağıdan ilk adresinizi ekleyin.</p>
        </div>
    @else
        <div class="address-grid">
            @foreach ($addresses as $address)
                <article class="address-card {{ $address->is_default ? 'is-default' : '' }}">
                    <header>
                        <h3>{{ $address->title }}</h3>
                        @if ($address->is_default)
                            <span class="badge badge--turq">Varsayılan</span>
                        @endif
                    </header>

                    <p><strong>{{ $address->recipient_name }}</strong>
                        @if ($address->recipient_phone)
                            <span class="muted"> · {{ $address->recipient_phone }}</span>
                        @endif
                    </p>

                    @if ($address->zone)
                        <p class="muted" style="font-size:.85rem">{{ $address->zone->name }}</p>
                    @endif

                    <p class="address-card__text">{{ $address->address }}</p>

                    <footer>
                        @unless ($address->is_default)
                            <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                                @csrf
                                <button type="submit" class="link-u">Varsayılan yap</button>
                            </form>
                        @endunless

                        <details class="address-card__edit">
                            <summary class="link-u">Düzenle</summary>
                            <form method="POST" action="{{ route('account.addresses.update', $address) }}" class="address-form">
                                @csrf
                                @method('PUT')
                                @include('account.partials.address-fields', ['address' => $address, 'zones' => $zones])
                                <button class="btn btn--rect btn--sm btn--block" type="submit">Kaydet</button>
                            </form>
                        </details>

                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}"
                              onsubmit="return confirm('Bu adres silinsin mi?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cart__remove">Sil</button>
                        </form>
                    </footer>
                </article>
            @endforeach
        </div>
    @endif

    <section class="acc-box" style="margin-top:2rem">
        <h2>Yeni adres ekle</h2>

        <form method="POST" action="{{ route('account.addresses.store') }}" class="address-form">
            @csrf
            @include('account.partials.address-fields', ['address' => null, 'zones' => $zones])
            <button class="btn btn--rect" type="submit">Adresi kaydet</button>
        </form>
    </section>
</x-layouts.account>
