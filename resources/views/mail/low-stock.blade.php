@component('mail::message')
# {{ $remaining <= 0 ? 'Stok bitti' : 'Stok azaldı' }}

**{{ $product->name }}** için kalan stok: **{{ $remaining }} adet**
(uyarı eşiği: {{ $threshold }})

@if ($product->has_variants)
Boy bazında kalanlar:

@foreach ($product->variants()->where('is_active', true)->orderBy('position')->get() as $variant)
- {{ $variant->name }}: {{ $variant->stock }} adet
@endforeach
@endif

@component('mail::button', ['url' => \App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $product])])
Ürünü panelde aç
@endcomponent

Stok girmezseniz ürün vitrinde "Tükendi" görünmeye devam eder.

{{ setting('shop_name', 'Ay Parçası') }}
@endcomponent
