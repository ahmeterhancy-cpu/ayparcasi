@props(['preview' => null, 'accent' => '#f4b02a'])

{{-- E-posta iskeleti.
     Posta istemcileri CSS değişkeni ve modern seçici desteklemez;
     bu yüzden renkler doğrudan yazılır ve yerleşim tablolarla kurulur. --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ setting('shop_name', 'Ay Parçası') }}</title>
    <style>
        body { margin:0; padding:0; background:#efe4d3; -webkit-text-size-adjust:100%; }
        table { border-collapse:collapse; }
        img { border:0; line-height:100%; display:block; }
        a { color:#16697f; }
        .wrap { width:100%; background:#efe4d3; padding:24px 12px; }
        .card { width:100%; max-width:600px; margin:0 auto; background:#fcf8f2; border-radius:14px; overflow:hidden; }
        .pad { padding:28px 32px; }
        .h1 { font-family:Georgia,'Times New Roman',serif; font-size:26px; line-height:1.2; color:#0e2c34; margin:0 0 10px; font-weight:normal; }
        .h2 { font-family:Georgia,'Times New Roman',serif; font-size:17px; color:#0e2c34; margin:0 0 10px; font-weight:normal; }
        .p { font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:15px; line-height:1.6; color:#274b53; margin:0 0 14px; }
        .small { font-size:13px; color:#5d7c83; }
        .btn { display:inline-block; padding:13px 26px; background:#db4a32; color:#ffffff !important; text-decoration:none; border-radius:8px; font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:15px; font-weight:bold; }
        .row td { font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:14px; color:#274b53; padding:7px 0; border-bottom:1px solid #efe4d3; vertical-align:top; }
        .row td.k { color:#5d7c83; width:38%; }
        .num { text-align:right; white-space:nowrap; }
        .total td { font-size:17px; font-weight:bold; color:#0e2c34; padding-top:12px; border-bottom:0; }
        @media (max-width:600px) {
            .pad { padding:22px 20px !important; }
            .h1 { font-size:22px !important; }
        }
    </style>
</head>
<body>
    @if ($preview)
        {{-- Gelen kutusunda konu satırının yanında görünen ön izleme --}}
        <div style="display:none;max-height:0;overflow:hidden;opacity:0">{{ $preview }}</div>
    @endif

    <table role="presentation" class="wrap" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" class="card" width="600">
                    {{-- Üst şerit: markanın mozaik/güneş rengi --}}
                    <tr>
                        <td style="height:6px;background:{{ $accent }};font-size:0;line-height:0">&nbsp;</td>
                    </tr>

                    <tr>
                        <td class="pad" style="padding-bottom:0">
                            {{-- İşaret + yazı, sitedeki başlıkla aynı düzen.
                                 Posta istemcileri görselleri varsayılan olarak
                                 engeller; bu yüzden marka adı METİN olarak kalıyor,
                                 görsel yüklenmese de başlık okunur oluyor. --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="padding-right:12px">
                                        <img src="{{ asset('img/mark.png') }}"
                                             width="48" height="44" alt=""
                                             style="display:block;width:48px;height:44px;border:0">
                                    </td>
                                    <td valign="middle">
                                        <div style="font-family:Georgia,'Times New Roman',serif;font-size:22px;color:#0e2c34">
                                            {{ setting('shop_name', 'Ay Parçası') }}
                                        </div>
                                        <div style="font-family:-apple-system,'Segoe UI',Roboto,Arial,sans-serif;font-size:11px;letter-spacing:1.6px;text-transform:uppercase;color:#16697f;padding-top:4px">
                                            {{ setting('tagline', 'Hediyelik Tasarımlar & Çiçekçi Dükkanı') }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="pad">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Alt bilgi --}}
                    <tr>
                        <td style="padding:20px 32px 28px;border-top:1px solid #efe4d3">
                            <p class="p small" style="margin:0">
                                @if (setting('phone'))
                                    {{ setting('phone') }} ·
                                @endif
                                @if (setting('email'))
                                    <a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a> ·
                                @endif
                                <a href="{{ wa_link('Merhaba,') }}">WhatsApp</a>
                            </p>

                            @if (setting('address'))
                                <p class="p small" style="margin:6px 0 0">{{ setting('address') }}</p>
                            @endif

                            <p class="p small" style="margin:12px 0 0;color:#8aa0a5">
                                Bu e-posta {{ setting('shop_name', 'Ay Parçası') }} siparişinizle ilgili gönderildi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
