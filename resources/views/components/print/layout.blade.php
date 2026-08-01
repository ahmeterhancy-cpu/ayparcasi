@props(['title', 'autoPrint' => true])

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} — {{ setting('shop_name', 'Ay Parçası') }}</title>

    <style>
        /* Baskıya özel, bağımsız stil — vitrin CSS'i yüklenmez */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            background: #eee;
            font: 13px/1.5 "Segoe UI", system-ui, -apple-system, sans-serif;
            color: #111;
        }
        .sheet {
            width: 190mm;
            min-height: 100mm;
            margin: 0 auto 12px;
            padding: 14mm;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
        }
        .bar {
            max-width: 190mm;
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .bar button, .bar a {
            padding: 8px 16px;
            border: 0;
            border-radius: 6px;
            background: #0e2c34;
            color: #fff;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .bar a { background: #6b7280; }

        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: .08em; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 0; vertical-align: top; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #666; border-bottom: 1px solid #ddd; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .muted { color: #666; }
        .rule { border: 0; border-top: 1px solid #ddd; margin: 10px 0; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border-bottom: 2px solid #111; padding-bottom: 10px; }
        .brand { font-size: 18px; font-weight: 700; }
        .facts { display: grid; grid-template-columns: auto 1fr; gap: 3px 14px; }
        .facts dt { color: #666; }
        .facts dd { margin: 0; font-weight: 600; }
        .total-row td { font-size: 16px; font-weight: 700; padding-top: 8px; }

        @media print {
            body { background: #fff; padding: 0; }
            .bar { display: none; }
            .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; page-break-after: always; }
            .sheet:last-child { page-break-after: auto; }
        }

        @page { size: A4; margin: 14mm; }
    </style>
</head>
<body>
    <div class="bar">
        <a href="javascript:history.back()">Geri</a>
        <button type="button" onclick="window.print()">Yazdır / PDF olarak kaydet</button>
    </div>

    {{ $slot }}

    @if ($autoPrint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400))</script>
    @endif
</body>
</html>
