<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliverySlot;
use App\Models\DeliveryZone;
use App\Models\Faq;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Vitrinin dolu görünmesi için örnek içerik.
 *
 * Görseller storage/app/public/demo altında YEREL olarak durur (hotlink yok).
 * İndiren betik: scratchpad/fetch_images.py — kaynaklar Unsplash ve
 * Wikimedia Commons. Gerçek ürün fotoğrafları geldiğinde admin panelinden
 * yüklenip bunların yerini alır.
 */
class DemoSeeder extends Seeder
{
    private function img(string $name): string
    {
        return 'demo/'.$name.'.jpg';
    }

    public function run(): void
    {
        $this->settings();
        $this->delivery();
        $this->addons();
        $cats = $this->categories();
        $this->products($cats);
        $this->content();
    }

    // ---------------------------------------------------------------------

    private function settings(): void
    {
        Setting::putMany([
            'shop_name' => 'Ay Parçası',
            'tagline' => 'Hediyelik Tasarımlar & Çiçekçi Dükkanı',
            'meta_description' => 'Kıbrıs\'ta el yapımı buketler, orkideler ve hediyelik tasarımlar. Saat 15:00\'e kadar verilen siparişlerde aynı gün teslimat.',

            // NOT: aşağıdaki iletişim bilgileri örnektir — gerçekleriyle değiştirilmeli.
            'phone' => '+90 533 000 00 00',
            'whatsapp' => '905330000000',
            'email' => 'merhaba@ayparcasicicekci.com',
            'address' => 'Örnek Sokak No. 1, Girne, Kuzey Kıbrıs',
            'hours' => 'Her gün 09:00 – 19:00',
            'instagram' => 'https://instagram.com/',
            'facebook' => '',

            'same_day_cutoff_hour' => 15,
            'bank_details' => "Hesap bilgilerini sipariş sonrası WhatsApp'tan paylaşıyoruz.",

            'order_emails_enabled' => true,
            'order_alert_email' => 'merhaba@ayparcasicicekci.com',
            'low_stock_threshold' => 3,

            'hero_eyebrow' => 'Kıbrıs · Aynı gün teslimat',
            'hero_title' => 'Bir çiçek, bir cümleden fazlasını söyler',
            'hero_subtitle' => 'Her buket dükkânımızda, siparişiniz geldikten sonra elde hazırlanır. Bugün sipariş verin, bugün kapısında olsun.',
            'hero_image' => $this->img('shop'),

            'about_title' => 'Küçük bir dükkân, uzun bir alışkanlık',
            'about_image' => $this->img('bouquet-thistle'),
            'footer_text' => 'Kıbrıs\'ta el yapımı buketler, orkideler ve hediyelik tasarımlar. Her buket dükkânımızda, sipariş üzerine hazırlanır.',
        ]);
    }

    private function delivery(): void
    {
        /*
         * Sıra ana sayfadaki teslimat hattıyla aynı: önce dükkânın günlük
         * güzergâhı (batıdan doğuya), sonra ada geneli.
         * ÜCRETLER PLACEHOLDER — gerçek rakamlar panelden girilmeli.
         */
        $zones = [
            ['Alsancak', 100, 2000, true, null],
            ['Karaoğlanoğlu', 100, 2000, true, null],
            ['Zeytinlik', 100, 2000, true, null],
            ['Girne', 150, 2500, true, 'Merkez.'],
            ['Karakum', 100, 2000, true, null],
            ['Ozanköy', 100, 2000, true, null],
            ['Çatalköy', 100, 2000, true, null],
            ['Lefkoşa', 200, 3000, true, 'Surlariçi dahil.'],
            ['Mağusa', 250, 3500, true, null],
            ['İskele', 300, 4000, false, 'Long Beach bölgesi dahil.'],
            ['Güzelyurt', 300, 4000, false, null],
            ['Lefke', 350, 4500, false, null],
        ];

        foreach ($zones as $i => [$name, $fee, $freeOver, $sameDay, $note]) {
            DeliveryZone::updateOrCreate(['name' => $name], [
                'fee' => $fee,
                'free_over' => $freeOver,
                'same_day' => $sameDay,
                'note' => $note,
                'position' => $i,
                'is_active' => true,
            ]);
        }

        foreach ([['09:00 – 12:00', '09:00', '12:00'], ['12:00 – 15:00', '12:00', '15:00'], ['15:00 – 19:00', '15:00', '19:00']] as $i => [$label, $s, $e]) {
            DeliverySlot::updateOrCreate(['label' => $label], [
                'starts_at' => $s,
                'ends_at' => $e,
                'position' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function addons(): void
    {
        $addons = [
            ['Belçika çikolatası', 'Küçük kutu, 12 parça', 450],
            ['Cam vazo', 'Buketiniz vazoyla gitsin', 600],
            ['Peluş ayıcık', '25 cm, krem rengi', 550],
            ['Uçan balon', 'Helyumlu, tek balon', 250],
        ];

        foreach ($addons as $i => [$name, $desc, $price]) {
            Addon::updateOrCreate(['name' => $name], [
                'description' => $desc,
                'price' => $price,
                'position' => $i,
                'is_active' => true,
            ]);
        }
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $tree = [
            'Çiçekler' => [
                'desc' => 'Mevsimin en tazesi, elde bağlanan buketler.',
                'img' => 'bouquet-cream',
                'children' => [
                    'Orkideler' => ['Uzun ömürlü, bakımı kolay; ofis ve ev için.', 'orchid-5', true],
                    'Güller' => ['Klasik hiç eskimez — tek dal ya da kucak dolusu.', 'roses-vase', false],
                    'Buketler' => ['Karışık mevsim çiçekleriyle elde bağlanmış.', 'bouquet-thistle', false],
                    'Aranjmanlar' => ['Vazoda ya da kutuda, hazır sunumlu.', 'tulips-vase', false],
                ],
            ],
            'Özel Günler' => [
                'desc' => 'Sebebini söyleyin, gerisini biz düşünelim.',
                'img' => 'heart-bouquet',
                'children' => [
                    'Yıldönümü' => ['Kaçıncı yıl olursa olsun, hatırlandığını bilsin.', 'roses-blue', true],
                    'Doğum Günü' => ['Rengarenk, neşeli ve biraz sürprizli.', 'roses-rainbow', true],
                    'Sevgililer Günü' => ['14 Şubat için hazırlanan özel tasarımlar.', 'heart-bouquet', true],
                    'Sevgiliye Özel' => ['Sebep gerekmez — sadece aklınıza geldiği için.', 'rose-single', true],
                    'Anneler Günü' => ['Annenizin en sevdiği renkleri biliyorsanız söyleyin.', 'dahlia-pastel', true],
                    'Sonbahar Çiçekleri' => ['Toprak tonları, kuru dallar ve sıcak renkler.', 'bouquet-dark', false],
                    'Yeni Yıl Çiçekleri' => ['Yılbaşı sofrasına ve yeni başlangıçlara.', 'roses-field', false],
                ],
            ],
            'Hediyelik' => [
                'desc' => 'Çiçeğin yanına ya da tek başına.',
                'img' => 'lily-pink',
                'children' => [
                    'Saksı Bitkiler' => ['Yıllarca kalsın isteyenler için.', 'peacelily-1', false],
                    'Hediye Kutuları' => ['Çiçek, çikolata ve küçük sürprizler bir arada.', 'tulip-pink', false],
                ],
            ],
        ];

        $map = [];
        $rootPos = 0;

        foreach ($tree as $name => $data) {
            $root = Category::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'description' => $data['desc'],
                'image' => $this->img($data['img']),
                'position' => $rootPos++,
                'is_active' => true,
            ]);

            $map[$name] = $root;
            $childPos = 0;

            foreach ($data['children'] as $childName => [$desc, $img, $featured]) {
                $map[$childName] = Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'parent_id' => $root->id,
                        'description' => $desc,
                        'image' => $this->img($img),
                        'position' => $childPos++,
                        'is_featured' => $featured,
                        'is_active' => true,
                    ]
                );
            }
        }

        return $map;
    }

    /** @param array<string, Category> $cats */
    private function products(array $cats): void
    {
        // [ad, kategoriler, taban fiyat, üstü çizili, görseller, öne çıkan, rozet, varyantlı]
        $items = [
            ['Beyaz Falenopsis Orkide', ['Orkideler', 'Hediyelik'], 2400, 2900, ['orchid-5', 'orchid-8'], true, 'Çok satan', false],
            ['Çift Dallı Mor Orkide', ['Orkideler'], 3200, null, ['orchid-3', 'orchid-7'], true, null, false],
            ['Mini Orkide Trio', ['Orkideler', 'Saksı Bitkiler'], 1850, 2200, ['orchid-4', 'orchid-1'], false, null, false],
            ['Seramik Saksıda Orkide', ['Orkideler', 'Hediye Kutuları'], 2750, null, ['orchid-2', 'orchid-6'], false, null, false],

            ['Kırmızı Gül Buketi', ['Güller', 'Sevgililer Günü', 'Sevgiliye Özel'], 1900, null, ['roses-field', 'heart-bouquet'], true, 'Klasik', true],
            ['Pastel Gül Aranjmanı', ['Güller', 'Aranjmanlar'], 2200, 2600, ['roses-vase', 'dahlia-pastel'], false, null, true],
            ['Beyaz Gül & Lisyantus', ['Güller', 'Yıldönümü'], 2650, null, ['bouquet-cream', 'roses-vase'], true, null, false],
            ['Kutuda Kırmızı Güller', ['Güller', 'Sevgililer Günü', 'Hediye Kutuları'], 3400, 3900, ['heart-bouquet', 'roses-field'], true, 'Sevgililer', false],

            ['Mevsim Buketi', ['Buketler'], 1450, null, ['bouquet-thistle', 'bouquet-cream'], true, null, true],
            ['Papatya & Şakayık', ['Buketler', 'Doğum Günü'], 1650, 1950, ['dahlia-pastel', 'blossom'], false, null, false],
            ['Lavanta Esintisi', ['Buketler'], 1350, null, ['allium', 'field-poppy'], false, null, false],
            ['Beyaz Zambak Buketi', ['Buketler', 'Yıldönümü'], 2100, null, ['lily-pink', 'bouquet-cream'], false, null, false],
            ['Kır Çiçekleri Demeti', ['Buketler', 'Doğum Günü'], 1250, 1500, ['field-poppy', 'allium'], false, 'Uygun', false],
            ['Şakayık Kucağı', ['Buketler', 'Anneler Günü'], 2950, null, ['blossom', 'dahlia-pastel'], true, 'Mevsim', true],

            ['Cam Vazoda Karışık Aranjman', ['Aranjmanlar'], 2350, null, ['tulips-vase', 'roses-vase'], false, null, false],
            ['Alçak Masa Aranjmanı', ['Aranjmanlar'], 1750, 2100, ['bouquet-cream', 'tulips-vase'], false, null, false],
            ['Silindir Vazoda Beyaz', ['Aranjmanlar', 'Yıldönümü'], 2500, null, ['roses-vase', 'lily-pink'], false, null, false],

            ['Yıldönümü Kırmızı & Beyaz', ['Yıldönümü', 'Güller'], 2850, 3300, ['roses-blue', 'roses-field'], true, null, false],
            ['On Yıl Kutlaması', ['Yıldönümü', 'Aranjmanlar'], 4200, null, ['bouquet-dark', 'roses-blue'], false, 'Özel', false],

            ['Doğum Günü Renk Cümbüşü', ['Doğum Günü'], 1550, null, ['roses-rainbow', 'sunflower'], true, null, true],
            ['Balonlu Doğum Günü Seti', ['Doğum Günü', 'Hediye Kutuları'], 2250, 2600, ['sunflower', 'roses-rainbow'], false, null, false],
            ['Turuncu Gerbera Buketi', ['Doğum Günü', 'Buketler'], 1350, null, ['field-crocus', 'sunflower'], false, null, false],

            ['Sevgililer Günü Klasiği', ['Sevgililer Günü', 'Güller'], 3100, 3600, ['heart-bouquet', 'roses-field'], true, '14 Şubat', false],
            ['Kalp Kutuda Güller', ['Sevgililer Günü', 'Hediye Kutuları'], 3800, null, ['roses-field', 'heart-bouquet'], false, null, false],
            ['Tek Dal Kırmızı Gül', ['Sevgiliye Özel', 'Güller'], 450, null, ['rose-single', 'roses-vase'], false, 'Uygun', false],
            ['Sebepsiz Buket', ['Sevgiliye Özel', 'Buketler'], 1250, null, ['bouquet-cream', 'bouquet-thistle'], false, null, false],
            ['Pembe Tonlarda Sürpriz', ['Sevgiliye Özel'], 1950, 2300, ['tulip-pink', 'roses-vase'], false, null, false],

            ['Anneler Günü Şakayık', ['Anneler Günü', 'Buketler'], 2450, null, ['dahlia-pastel', 'blossom'], true, 'Anneler Günü', false],
            ['Anneme Özel Aranjman', ['Anneler Günü', 'Aranjmanlar'], 2850, 3200, ['tulips-vase', 'lily-pink'], false, null, false],
            ['Beyaz & Pembe Zarafet', ['Anneler Günü'], 2150, null, ['roses-vase', 'tulip-pink'], false, null, false],

            ['Sonbahar Toprak Tonları', ['Sonbahar Çiçekleri', 'Buketler'], 1750, null, ['bouquet-dark', 'allium'], false, null, false],
            ['Kuru Çiçek Demeti', ['Sonbahar Çiçekleri'], 1450, 1700, ['allium', 'bouquet-dark'], false, 'Uzun ömürlü', false],
            ['Kestane & Krizantem', ['Sonbahar Çiçekleri', 'Aranjmanlar'], 1950, null, ['field-crocus', 'bouquet-dark'], false, null, false],

            ['Yılbaşı Masa Aranjmanı', ['Yeni Yıl Çiçekleri', 'Aranjmanlar'], 2400, null, ['roses-blue', 'bouquet-dark'], false, null, false],
            ['Yeni Yıl Kırmızısı', ['Yeni Yıl Çiçekleri'], 2100, 2500, ['roses-field', 'roses-blue'], false, null, false],

            ['Barış Çiçeği (Spatifilyum)', ['Saksı Bitkiler'], 1250, null, ['peacelily-1', 'peacelily-2'], false, null, false],
            ['Sukulent Üçlüsü', ['Saksı Bitkiler', 'Hediyelik'], 850, 1050, ['succulent-1', 'succulent-2'], false, 'Uygun', false],
            ['Salon Bitkisi', ['Saksı Bitkiler'], 2200, null, ['interior-plant', 'peacelily-2'], false, null, false],

            ['Çikolata & Çiçek Kutusu', ['Hediye Kutuları', 'Hediyelik'], 2650, null, ['heart-bouquet', 'tulip-pink'], true, null, false],
            ['Küçük Sürpriz Kutusu', ['Hediye Kutuları'], 1450, 1700, ['tulip-pink', 'lily-pink'], false, null, false],
        ];

        foreach ($items as $pos => [$name, $catNames, $price, $compare, $imgs, $featured, $badge, $hasVariants]) {
            $gallery = array_map(fn ($k) => $this->img($k), $imgs);

            $product = Product::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'short_description' => $this->shortFor($name),
                'description' => "Bu tasarım siparişiniz düştükten sonra dükkânımızda elde hazırlanır. Görseldeki kompozisyon örnektir; mevsime ve o günkü tazeliğe göre benzer çiçeklerle en iyi hâline getirilir.\n\nSu haznesiyle paketlenir, kart notunuz elle yazılıp yanına iliştirilir.",
                'contents' => $this->contentsFor($name),
                'care_notes' => "Serin, doğrudan güneş almayan bir yere koyun.\nVazo suyunu iki günde bir değiştirin.\nSap uçlarını her su değişiminde 1–2 cm eğik kesin.\nOlgunlaşan meyvelerin yanında bırakmayın; çiçekler çabuk solar.",
                'price' => $price,
                'compare_at_price' => $compare,
                'track_stock' => true,
                'stock' => $hasVariants ? 0 : random_int(3, 25),
                'hero_image' => $gallery[0],
                'gallery' => array_slice($gallery, 1),
                'badge' => $badge,
                'is_active' => true,
                'is_featured' => $featured,
                'same_day' => true,
                // Puan uydurulmaz. rating/review_count yalnızca onaylanmış
                // müşteri yorumlarından hesaplanır (App\Models\Review).
                'position' => $pos,
            ]);

            $ids = collect($catNames)->map(fn ($n) => $cats[$n]->id ?? null)->filter()->all();
            $product->categories()->sync($ids);

            if ($hasVariants) {
                $product->variants()->delete();

                $sizes = [
                    ['Küçük', '9 dal', 1.0, 6],
                    ['Orta', '15 dal', 1.45, 8],
                    ['Büyük', '25 dal', 2.05, 4],
                ];

                foreach ($sizes as $i => [$vName, $vDesc, $mult, $stock]) {
                    $product->variants()->create([
                        'name' => $vName,
                        'description' => $vDesc,
                        'price' => round($price * $mult / 50) * 50,
                        'compare_at_price' => $compare ? round($compare * $mult / 50) * 50 : null,
                        'stock' => $stock,
                        'is_default' => $i === 1,
                        'is_active' => true,
                        'position' => $i,
                    ]);
                }
            }
        }
    }

    private function shortFor(string $name): string
    {
        $map = [
            'orkide' => 'Aylarca çiçekte kalır; haftada bir sulamak yeter.',
            'gül' => 'Sabah gelen partiden, açmamış goncalarla bağlanır.',
            'buket' => 'Mevsimin en tazesiyle, elde bağlanmış.',
            'aranjman' => 'Vazosuyla gider; yerleştirmeye gerek kalmaz.',
            'kutu' => 'Kutusuyla birlikte sunuma hazır.',
            'saksı' => 'Uzun ömürlü, bakımı kolay bir yeşillik.',
        ];

        foreach ($map as $key => $text) {
            if (mb_stripos($name, $key) !== false) {
                return $text;
            }
        }

        return 'Siparişten sonra dükkânımızda elde hazırlanır.';
    }

    private function contentsFor(string $name): string
    {
        if (mb_stripos($name, 'orkide') !== false) {
            return "Falenopsis orkide\nSeramik ya da cam saksı\nYosun kaplama\nBakım kartı";
        }

        if (mb_stripos($name, 'gül') !== false) {
            return "İthal gül\nOkaliptus dalı\nKraft kâğıt sarım\nSaten kurdele\nSu haznesi";
        }

        return "Mevsim çiçekleri\nYeşillik dalları\nKraft kâğıt sarım\nSaten kurdele\nSu haznesi";
    }

    private function content(): void
    {
        $faqs = [
            ['Aynı gün teslimat yapıyor musunuz?', "Evet. Saat 15:00'e kadar verilen siparişleri Girne, Lefkoşa ve Mağusa'ya aynı gün teslim ediyoruz. Diğer bölgelerde en erken ertesi gün."],
            ['Görseldeki buketin aynısı mı gelecek?', 'Kompozisyon ve renk uyumu aynı olur; tek tek çiçekler o günkü tazeliğe göre değişebilir. Elimizde en iyisi neyse onu kullanırız — hiçbir zaman daha azını değil.'],
            ['Kart notu ekleyebilir miyim?', 'Elbette. Sipariş sırasında yazdığınız notu elle yazıp buketin yanına iliştiriyoruz. Ücretsiz.'],
            ['Alıcı evde yoksa ne oluyor?', 'Önce alıcıyı arıyoruz. Ulaşamazsak sizi arayıp yönlendirmenizi istiyoruz; komşuya bırakma ya da ikinci teslimat için ek ücret almıyoruz.'],
            ['Ödemeyi nasıl yapabilirim?', 'Kredi/banka kartıyla siteden, kapıda nakit ya da kartla, havale/EFT ile veya siparişi WhatsApp üzerinden tamamlayarak ödeyebilirsiniz.'],
            ['Çiçek solarsa ne yapıyorsunuz?', 'Teslimattan sonraki 24 saat içinde bir sorun olursa fotoğrafını gönderin; yenisini ücretsiz yolluyoruz.'],
            ['Sürpriz gönderebilir miyim?', 'Evet. "İsmim yazmasın" kutusunu işaretlerseniz kartta gönderen adı yer almaz; kurye de kimden geldiğini söylemez.'],
            ['Kurumsal sipariş veriyor musunuz?', 'Düzenli ofis aranjmanı, açılış çelengi ve toplu gönderim yapıyoruz. WhatsApp\'tan yazın, size özel fiyat çıkaralım.'],
        ];

        foreach ($faqs as $i => [$q, $a]) {
            Faq::updateOrCreate(['question' => $q], ['answer' => $a, 'position' => $i, 'is_active' => true]);
        }

        $testimonials = [
            ['Selin A.', 'Girne', 5, 'Annemin doğum gününde sabah sipariş verdim, öğleden sonra kapısındaydı. Fotoğrafını da gönderdiler, çok memnun kaldım.'],
            ['Mert K.', 'Lefkoşa', 5, 'Kart notunu elle yazmışlar. Bu detay bile tek başına fark yaratıyor.'],
            ['Deniz Y.', 'Mağusa', 5, 'Aldığım orkide üç aydır çiçekte. Nasıl bakacağımı da anlattılar, gerçekten işini bilen insanlar.'],
            ['Ayşe T.', 'Girne', 5, 'WhatsApp\'tan ne göndereceğime karar veremediğimi söyledim, üç seçenek hazırlayıp fotoğraf attılar. On dakikada halloldu.'],
            ['Burak S.', 'İskele', 4, 'Buket görseldekinden bile güzeldi. Tek eksik, bizim bölgeye aynı gün gelmiyor olması.'],
            ['Nazlı Ö.', 'Lefkoşa', 5, 'Sevgililer gününde herkes zam yaparken burası fiyatını değiştirmemişti. Bunu unutmam.'],
        ];

        foreach ($testimonials as $i => [$name, $city, $rating, $body]) {
            Testimonial::updateOrCreate(['name' => $name], [
                'city' => $city,
                'rating' => $rating,
                'body' => $body,
                'position' => $i,
                'is_active' => true,
            ]);
        }

        $posts = [
            [
                'Kesme çiçek nasıl daha uzun dayanır?',
                'Vazoya koymadan önceki ilk beş dakika, buketin ömrünün yarısını belirliyor.',
                "Çiçek eve geldiğinde yapılacak ilk şey, vazoyu doldurmak değil — sapları kesmek.\n\nSapların ucu, yoldayken hava alır ve kılcal damarlar tıkanır. Su çekemeyen bir sap, vazonun içinde susuz kalır. Bu yüzden vazoya koymadan hemen önce, tercihen su altında, 45 derece açıyla 1–2 cm kesin.\n\nİkincisi: suyun içinde kalan bütün yaprakları temizleyin. Suda kalan yaprak çürür, bakteri üretir, su bulanır ve çiçek iki günde biter.\n\nSuyu iki günde bir değiştirin, her değişimde sapları tekrar kesin. Vazoyu doğrudan güneşten, kaloriferden ve meyve tabağından uzak tutun — olgunlaşan meyve etilen gazı salar, çiçekleri hızla yaşlandırır.\n\nBu dört adım, ortalama bir buketin ömrünü beş günden on güne çıkarır.",
                'field-crocus',
            ],
            [
                'Orkide bakımı: az müdahale, çok sabır',
                'Orkideleri öldüren şey ihmal değil, fazla ilgi.',
                "Orkide satın alan çoğu kişi aynı hatayı yapıyor: çok su vermek.\n\nFalenopsis orkide, doğada ağaç gövdelerinde yaşar. Kökleri havayla temas etmek ister. Saksının dibinde biriken su, kökleri birkaç haftada çürütür.\n\nDoğrusu şu: haftada bir kez, saksıyı lavaboya götürüp ılık suyun altında 20–30 saniye tutun. Suyun tamamının akıp gitmesini bekleyin, sonra yerine koyun. Tabağında su bırakmayın.\n\nIşık konusunda da benzer bir denge var: doğrudan güneş yaprakları yakar, karanlık köşede ise hiç çiçek açmaz. Perde arkası, kuzeye ya da doğuya bakan bir pencere kenarı idealdir.\n\nÇiçekler döküldüğünde bitkiyi atmayın. Çiçek sapını, alttan ikinci düğümün 1 cm üstünden kesin; çoğu zaman iki üç ay içinde aynı saptan yeni bir dal çıkar.",
                'orchid-5',
            ],
            [
                'Hangi çiçek, hangi anlama gelir?',
                'Renk seçimi, kart notundan önce gelen ilk mesajdır.',
                "Çiçek göndermek isteyip de hangisini seçeceğine karar veremeyenler için kısa bir rehber.\n\nKırmızı gül: tartışmasız aşk. Başka hiçbir şey söylemez, söylemesi de gerekmez.\n\nBeyaz çiçekler (zambak, lisyantus, beyaz gül): saygı, sadelik, yeni başlangıç. Taziyede de, açılışta da, yıldönümünde de yanlış olmaz.\n\nSarı ve turuncu tonlar: neşe ve arkadaşlık. Doğum günü için en güvenli seçim. Romantik bir mesaj vermek istiyorsanız kaçının.\n\nPembe: sevgi ama daha yumuşak bir tonda. Anneye, kardeşe, yeni tanışılan birine.\n\nMor ve lila: zarafet ve hayranlık. \"Seni takdir ediyorum\" demenin çiçekle söylenmiş hâli.\n\nKarar veremiyorsanız mevsim buketi her zaman doğru cevaptır — çünkü o gün en taze olan neyse ondan yapılır.",
                'field-poppy',
            ],
        ];

        foreach ($posts as $i => [$title, $excerpt, $body, $img]) {
            Post::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => $body,
                'cover' => $this->img($img),
                'published_at' => now()->subDays(($i + 1) * 9),
                'is_active' => true,
            ]);
        }

        Banner::updateOrCreate(['placement' => 'strip'], [
            'title' => 'Saat 15:00\'e kadar verilen siparişler bugün teslim edilir.',
            'link' => '/teslimat',
            'cta_label' => 'Bölgeleri gör',
            'is_active' => true,
            'position' => 0,
        ]);

        Coupon::updateOrCreate(['code' => 'HOSGELDIN'], [
            'type' => 'percent',
            'value' => 10,
            'min_total' => 1500,
            'is_active' => true,
        ]);
    }
}
