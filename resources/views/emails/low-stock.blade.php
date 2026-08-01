@php $variants = $product->has_variants ? $product->variants()->where('is_active', true)->orderBy('position')->get() : collect(); @endphp

<x-mail.shell :preview="$product->name.' — '.$remaining.' adet kaldı'" accent="#db4a32">
    <h1 class="h1">{{ $remaining <= 0 ? 'Stok bitti' : 'Stok azaldı' }}</h1>

    <p class="p">
        <strong>{{ $product->name }}</strong> için kalan stok:
        <strong style="color:{{ $remaining <= 0 ? '#db4a32' : '#b4701a' }}">{{ $remaining }} adet</strong>
        <span class="small">(uyarı eşiği: {{ $threshold }})</span>
    </p>

    @if ($variants->isNotEmpty())
        <table role="presentation" width="100%" style="margin:14px 0">
            @foreach ($variants as $variant)
                <tr class="row">
                    <td class="k">{{ $variant->name }}</td>
                    <td class="num">{{ $variant->stock }} adet</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p style="margin:22px 0 6px">
        <a class="btn" href="{{ $productUrl }}">Ürünü panelde aç</a>
    </p>

    <p class="p small" style="margin-top:18px">
        Stok girmezseniz ürün vitrinde “Tükendi” görünmeye devam eder.
    </p>
</x-mail.shell>
