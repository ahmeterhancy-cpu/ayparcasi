{{-- Marka görseli iki biçimde basılır; hangisinin görüneceğini CSS seçer:
     - kenar çubuğu / üst çubuk → yatay kilit (işaret + yazı)
     - giriş ve parola ekranları → yalnız işaret, büyük
     Kilit 3:1 olduğu için küçük boyutta alt yazısı okunmuyor; bu yüzden
     dar alanlarda kilit, geniş ve tek odaklı alanlarda işaret kullanılır. --}}

<img
    class="ay-brand-lockup"
    src="{{ asset('img/logo.png') }}"
    alt="Ay Parçası — Hediyelik Tasarımlar &amp; Çiçekçi Dükkanı"
    width="680"
    height="227"
>

<img
    class="ay-brand-mark"
    src="{{ asset('img/mark.png') }}"
    alt="Ay Parçası"
    width="320"
    height="293"
>
