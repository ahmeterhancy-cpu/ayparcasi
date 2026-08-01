@php
    use App\Services\SalesReport;

    $report = $this->report();
    $now = $report->totals();
    $prev = $report->previous()->totals();
    $daily = $report->daily();
    $maxRevenue = max(1, $daily->max('revenue'));

    $cards = [
        ['Ciro', money($now['revenue']), SalesReport::change($now['revenue'], $prev['revenue'])],
        ['Sipariş', number_format($now['orders'], 0, ',', '.'), SalesReport::change($now['orders'], $prev['orders'])],
        ['Ortalama sepet', money($now['average']), SalesReport::change($now['average'], $prev['average'])],
        ['Teslim edilen', number_format($now['delivered'], 0, ',', '.'), SalesReport::change($now['delivered'], $prev['delivered'])],
    ];
@endphp

<x-filament-panels::page>
    {{-- Filament'in derlenmiş CSS'i yalnızca kendi kullandığı sınıfları içerir,
         bu yüzden bu sayfanın düzeni kendi stiliyle gelir. --}}
    <style>
        .rp-grid { display: grid; gap: 1rem; }
        .rp-kpis { grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
        .rp-two { grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr)); }

        .rp-box {
            padding: 1.1rem 1.25rem;
            border-radius: .75rem;
            background: var(--fi-color-white, #fff);
            box-shadow: 0 1px 3px rgb(0 0 0 / .07);
            border: 1px solid rgb(0 0 0 / .06);
        }
        .dark .rp-box { background: rgb(24 24 27); border-color: rgb(255 255 255 / .1); }

        .rp-label { font-size: .82rem; opacity: .7; }
        .rp-value { font-size: 1.6rem; font-weight: 700; line-height: 1.15; margin-top: .15rem; letter-spacing: -.02em; }
        .rp-delta { font-size: .78rem; font-weight: 600; margin-top: .2rem; }
        .rp-up { color: #16a34a; }
        .rp-down { color: #dc2626; }
        .rp-flat { opacity: .55; font-weight: 400; }

        .rp-chart { display: flex; align-items: flex-end; gap: 3px; height: 170px; }
        /* max-width: tek günlük aralıkta dev bir blok görünmesin */
        .rp-chart > div { flex: 1; max-width: 46px; display: flex; flex-direction: column; justify-content: flex-end; height: 100%; }
        .rp-bar { border-radius: 3px 3px 0 0; background: var(--primary-500, #16697f); min-height: 2px; }
        .rp-bar[data-empty="1"] { background: rgb(128 128 128 / .22); }
        .rp-axis { display: flex; justify-content: space-between; font-size: .75rem; opacity: .6; margin-top: .5rem; }

        .rp-h3 { font-weight: 600; margin-bottom: .75rem; }
        .rp-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .rp-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; opacity: .6; padding-bottom: .4rem; text-align: right; }
        .rp-table th:first-child, .rp-table td:first-child { text-align: left; }
        .rp-table td { padding: .35rem 0; text-align: right; font-variant-numeric: tabular-nums; border-top: 1px solid rgb(128 128 128 / .18); }
        .rp-meta { font-size: .85rem; opacity: .7; margin-bottom: .25rem; }
    </style>

    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    <p class="rp-meta">
        {{ $report->from->translatedFormat('d F Y') }} – {{ $report->to->translatedFormat('d F Y') }}
        · önceki dönemle karşılaştırılıyor · iptal edilen siparişler hariç
    </p>

    <div class="rp-grid rp-kpis">
        @foreach ($cards as [$label, $value, $change])
            <div class="rp-box">
                <div class="rp-label">{{ $label }}</div>
                <div class="rp-value">{{ $value }}</div>

                @if ($change !== null)
                    <div class="rp-delta {{ $change >= 0 ? 'rp-up' : 'rp-down' }}">
                        {{ $change >= 0 ? '▲' : '▼' }} %{{ number_format(abs($change), 1, ',', '.') }}
                        <span class="rp-flat">önceki döneme göre</span>
                    </div>
                @else
                    <div class="rp-delta rp-flat">önceki dönemde veri yok</div>
                @endif
            </div>
        @endforeach
    </div>

    @if ((float) $now['refunded'] > 0)
        <div class="rp-box">
            Bu dönemde <strong>{{ money($now['refunded']) }}</strong> iade edildi.
            Net ciro: <strong>{{ money($now['net']) }}</strong>
        </div>
    @endif

    <div class="rp-box">
        <div class="rp-h3">Günlük ciro</div>

        @if ($daily->isEmpty())
            <p class="rp-label">Bu aralıkta veri yok.</p>
        @else
            <div class="rp-chart">
                @foreach ($daily as $day)
                    <div title="{{ $day['label'] }} — {{ money($day['revenue']) }} ({{ $day['orders'] }} sipariş)">
                        <div class="rp-bar"
                             data-empty="{{ $day['revenue'] > 0 ? '0' : '1' }}"
                             style="height: {{ max(2, round($day['revenue'] / $maxRevenue * 100)) }}%"></div>
                    </div>
                @endforeach
            </div>

            <div class="rp-axis">
                <span>{{ $daily->first()['label'] }}</span>
                <span>en yüksek gün: {{ money($maxRevenue) }}</span>
                <span>{{ $daily->last()['label'] }}</span>
            </div>
        @endif
    </div>

    <div class="rp-grid rp-two">
        <div class="rp-box">
            <div class="rp-h3">En çok satanlar</div>
            @include('filament.pages.partials.report-table', [
                'rows' => $report->topProducts(),
                'cols' => ['Ürün' => 'name', 'Adet' => 'qty', 'Ciro' => 'revenue'],
            ])
        </div>

        <div class="rp-box">
            <div class="rp-h3">Kategoriler</div>
            @include('filament.pages.partials.report-table', [
                'rows' => $report->byCategory(),
                'cols' => ['Kategori' => 'name', 'Adet' => 'qty', 'Ciro' => 'revenue'],
            ])
        </div>

        <div class="rp-box">
            <div class="rp-h3">Teslimat bölgeleri</div>
            @include('filament.pages.partials.report-table', [
                'rows' => $report->byZone(),
                'cols' => ['Bölge' => 'zone', 'Sipariş' => 'c', 'Ciro' => 'revenue'],
            ])
        </div>

        <div class="rp-box">
            <div class="rp-h3">Ödeme yöntemleri</div>
            @include('filament.pages.partials.report-table', [
                'rows' => $report->byPaymentMethod()->map(fn ($r) => (object) [
                    'name' => \App\Models\Order::PAYMENT_METHODS[$r->payment_method] ?? $r->payment_method,
                    'c' => $r->c,
                    'revenue' => $r->revenue,
                ]),
                'cols' => ['Yöntem' => 'name', 'Sipariş' => 'c', 'Ciro' => 'revenue'],
            ])
        </div>
    </div>

    @if ($report->byCoupon()->isNotEmpty())
        <div class="rp-box">
            <div class="rp-h3">Kullanılan kuponlar</div>
            @include('filament.pages.partials.report-table', [
                'rows' => $report->byCoupon(),
                'cols' => ['Kupon' => 'coupon_code', 'Kullanım' => 'c', 'Verilen indirim' => 'discount', 'Ciro' => 'revenue'],
            ])
        </div>
    @endif
</x-filament-panels::page>
