<x-mail.shell preview="E-posta ayarlarınız çalışıyor.">
    <h1 class="h1">E-posta ayarları çalışıyor</h1>

    <p class="p">
        Bu bir deneme gönderimidir. Bu e-postayı gördüğünüze göre
        {{ setting('shop_name', 'Ay Parçası') }} sipariş bildirimlerini gönderebilecek.
    </p>

    <p class="p">Müşterilere gidecek e-postalar şunlar:</p>

    <table role="presentation" width="100%">
        <tr class="row"><td class="k">Sipariş alındı</td><td>sipariş verilir verilmez, özet ve teslimat bilgileriyle</td></tr>
        <tr class="row"><td class="k">Durum değişti</td><td>onaylandı · hazırlanıyor · yolda · teslim edildi · iptal</td></tr>
        <tr class="row"><td class="k">Ödeme alındı</td><td>kartla ödeme onaylandığında</td></tr>
        <tr class="row"><td class="k">Yeni sipariş</td><td>ekibe bildirim (bu adrese)</td></tr>
    </table>

    <p class="p small" style="margin-top:20px">
        Sipariş e-postalarını Panel › Site ayarları › Sipariş sekmesinden kapatabilirsiniz.
    </p>
</x-mail.shell>
