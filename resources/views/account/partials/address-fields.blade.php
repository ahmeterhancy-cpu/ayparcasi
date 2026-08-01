@php $a = $address ?? null; @endphp

<div class="grid-2">
    <div class="field">
        <label>Adres başlığı *</label>
        <input class="input" type="text" name="title" required maxlength="60"
               placeholder="Ev, Ofis, Annem…" value="{{ old('title', $a?->title) }}">
    </div>

    <div class="field">
        <label>Bölge</label>
        <select class="select" name="delivery_zone_id">
            <option value="">Seçiniz</option>
            @foreach ($zones as $zone)
                <option value="{{ $zone->id }}" @selected(old('delivery_zone_id', $a?->delivery_zone_id) == $zone->id)>
                    {{ $zone->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid-2">
    <div class="field">
        <label>Alıcı adı *</label>
        <input class="input" type="text" name="recipient_name" required maxlength="120"
               value="{{ old('recipient_name', $a?->recipient_name ?? auth()->user()->name) }}">
    </div>

    <div class="field">
        <label>Alıcı telefonu</label>
        <input class="input" type="tel" name="recipient_phone" maxlength="40"
               value="{{ old('recipient_phone', $a?->recipient_phone ?? auth()->user()->phone) }}">
    </div>
</div>

<div class="field">
    <label>Açık adres *</label>
    <textarea class="textarea" name="address" required maxlength="600"
              placeholder="Sokak, bina, daire, tarif…">{{ old('address', $a?->address) }}</textarea>
</div>

<label class="choice choice--check">
    <input type="checkbox" name="is_default" value="1" @checked($a?->is_default)>
    <span class="choice__dot" aria-hidden="true"></span>
    <span class="choice__text"><span class="choice__title">Varsayılan adresim olsun</span></span>
</label>
