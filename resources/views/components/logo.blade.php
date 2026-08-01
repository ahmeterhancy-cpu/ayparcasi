@props(['class' => ''])

{{-- Ay Parçası marka işareti — gerçek logodan kırpılmıştır
     (kaynak: resources/images/logo-source.png).

     Tam yatay kilit (public/img/logo.png) yalnız geniş alanlarda kullanılır;
     başlık ve alt bilgi gibi dar yerlerde kilitteki alt yazı okunmayacak
     kadar küçüldüğü için işaret ile yazı ayrı ayrı diziliyor.

     alt boş: yanında marka adı zaten metin olarak duruyor. --}}
<img
    {{ $attributes->merge(['class' => $class]) }}
    src="{{ asset('img/mark.png') }}"
    alt=""
    width="320"
    height="293"
    decoding="async"
>
