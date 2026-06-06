<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Factura {{ $factura['numero'] ?? '' }}</title>
@php
    $pdfMode = (bool) ($pdfMode ?? false);
    $printSettings = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
    $invoiceHeaderColor = (string) ($printSettings['invoice_color_header'] ?? '#101729');
    $invoiceFooterColor = (string) ($printSettings['invoice_color_footer'] ?? '#f0fe97');
    if (!preg_match('/^#([A-Fa-f0-9]{6})$/', $invoiceHeaderColor)) {
        $invoiceHeaderColor = '#101729';
    }
    if (!preg_match('/^#([A-Fa-f0-9]{6})$/', $invoiceFooterColor)) {
        $invoiceFooterColor = '#f0fe97';
    }
    $invoiceLogoSizeMm = (int) ($printSettings['invoice_logo_size'] ?? 52);
    if ($invoiceLogoSizeMm < 24) $invoiceLogoSizeMm = 24;
    if ($invoiceLogoSizeMm > 90) $invoiceLogoSizeMm = 90;
    $fontFiles = [
        400 => public_path('fonts/Poppins-Regular.ttf'),
        500 => public_path('fonts/Poppins-Medium.ttf'),
        600 => public_path('fonts/Poppins-SemiBold.ttf'),
        700 => public_path('fonts/Poppins-Bold.ttf'),
        800 => public_path('fonts/Poppins-ExtraBold.ttf'),
    ];
@endphp
@unless($pdfMode)
@include('partials.favicon')
@endunless

<style>
@foreach($fontFiles as $fontWeight => $fontPath)
@if(is_file($fontPath))
@font-face{
    font-family:'Poppins';
    font-style:normal;
    font-weight:{{ $fontWeight }};
    src:url('{{ $fontPath }}') format('truetype');
}
@endif
@endforeach

/* =========================================
   PDF ENGINE OPTIMIZED
   Compatible:
   - DomPDF
   - Snappy/WKHTMLTOPDF
   - Browsershot
   - Laravel PDF
========================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html,
body{
    width:100%;
    height:100%;
}

body{
    font-family: 'Poppins', DejaVu Sans, Arial, Helvetica, sans-serif;
    background:#ffffff;
    color:#1f2937;
    font-size:12px;
    line-height:1.4;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}

body, body *{
    font-family: 'Poppins', DejaVu Sans, Arial, Helvetica, sans-serif !important;
}

table,
tr,
td,
th,
div,
span,
p{
    font-family: 'Poppins', DejaVu Sans, Arial, Helvetica, sans-serif;
}

/* PAGE */

@page{
    size:A4;
    margin:0;
}

.invoice-page{
    width:210mm;
    min-height:auto;
    margin:0 auto;
    padding:6mm 0;
    background:#ffffff;
}

/* MAIN CARD */

.invoice{
    width:182mm;
    margin:0 auto;
    border-radius:18px;
    overflow:hidden;
    border:1px solid #e7ebf1;
    background:#fff;
}

/* =========================================
   HEADER
========================================= */

.invoice-top{
    background:{{ $invoiceHeaderColor }};
    padding:12px 18px;
}

.header-table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.header-left{
    width:60%;
    vertical-align:top;
}

.header-right{
    width:40%;
    text-align:right;
    vertical-align:top;
}

.header-right-stack{
    display:block;
    text-align:right;
}

/* LOGO */

.company-logo{
    width:{{ $invoiceLogoSizeMm }}mm;
    max-width:100%;
    height:auto;
    margin-bottom:8px;
}

/* FALLBACK TEXT LOGO */

.company-title{
    font-size:34px;
    font-weight:800;
    margin-bottom:18px;
    line-height:1;
}

.green{
    color:#d7ea68;
}

.white{
    color:#ffffff;
}

.company-title small{
    font-size:12px;
    color:#ffffff;
    position:relative;
    top:-4px;
}

/* COMPANY INFO */

.company-info{
    color:#ffffff;
    font-size:11px;
    line-height:1.02;
    word-break:break-word;
}
.company-info,
.company-info *{
    color:#dbe6f5 !important;
    font-family: 'Poppins', DejaVu Sans, Arial, Helvetica, sans-serif !important;
}

.company-info-line{
    margin:0 0 1px 0;
}

.company-info-line:last-child{
    margin-bottom:0;
}

.company-main-line{
    white-space:nowrap;
    font-size:10px;
}

/* RIGHT SIDE */

.invoice-number{
    color:#d7ea68;
    font-size:16px;
    font-weight:800;
    margin-bottom:2px;
    text-transform:uppercase;
    word-break:break-word;
    line-height:1;
}

.total-top{
    color:#ffffff;
    font-size:14px;
    font-weight:700;
    margin-bottom:3px;
    line-height:1;
}

.total-top span{
    color:#ffffff !important;
    font-size:32px;
    font-weight:600;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    line-height:1;
}

.badge{
    display:inline-block;
    padding:3px 14px;
    border-radius:40px;
    font-size:10px;
    font-weight:600;
    letter-spacing:.5px;
    background:#d9f0e7;
    color:#007d58;
    line-height:1.2;
    margin-top:2px;
    text-align:center;
}

.badge-overdue{
    background:#ffe2e2;
    color:#b91c1c;
}

.badge-pending{
    background:#dbeafe;
    color:#1d4ed8;
}

.badge-partial{
    background:#fff4cf;
    color:#8a5a00;
}

/* =========================================
   CLIENT INFO
========================================= */

.invoice-info{
    padding:6px 18px 6px;
    border-bottom:1px solid #edf1f5;
}

.info-table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.info-col{
    vertical-align:top;
}

.info-col-left{
    width:50%;
}

.info-col-mid{
    width:25%;
}

.info-col-right{
    width:25%;
}

.label{
    color:#94a3b8;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.8px;
    margin-bottom:-2px;
}

.client-name{
    font-size:16px;
    font-weight:700;
    color:#1e293b;
    margin-top:1px;
    margin-bottom:0;
    line-height:1.05;
}

.client-address{
    color:#64748b;
    font-size:13px;
    line-height:1.02;
}

.client-address-line{
    margin:0 0 1px 0;
}

.client-address-line:last-child{
    margin-bottom:0;
}

.info-value{
    color:#334155;
    font-size:13px;
    font-weight:600;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    line-height:1.2;
    margin-top:0;
    line-height:1.05;
}

.info-col-mid .label,
.info-col-right .label{
    margin-bottom:-3px;
}
.label,
.client-name,
.client-address,
.client-address-line,
.info-value{
    font-family: 'Poppins', DejaVu Sans, Arial, Helvetica, sans-serif !important;
}

/* =========================================
   TABLE
========================================= */

.invoice-body{
    padding:12px 18px 4px;
}

.table{
    width:100%;
    border-collapse:collapse;
}

.table thead th{
    text-align:left;
    padding-bottom:6px;
    border-bottom:1px solid #e9edf2;
    color:#94a3b8;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
}

.table thead th:nth-child(2),
.table thead th:nth-child(3),
.table thead th:nth-child(4){
    text-align:center;
}

.table tbody td{
    padding:7px 0;
    border-bottom:1px solid #edf1f5;
    color:#1f2937;
    font-size:13px;
    font-family: 'Poppins', DejaVu Sans, Arial, Helvetica, sans-serif !important;
}

.table tbody td:nth-child(2),
.table tbody td:nth-child(3),
.table tbody td:nth-child(4){
    text-align:center;
}

.table tbody td:last-child{
    font-weight:700;
}

/* =========================================
   TOTALS
========================================= */

.totals-wrapper{
    width:100%;
    margin-top:8px;
}

.totals{
    width:240px;
    margin-left:auto;
}

.total-row{
    width:100%;
    margin-bottom:4px;
}

.total-table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.total-table td{
    font-size:14px;
    color:#64748b;
    width:50%;
    text-align:left;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif !important;
}

.total-table td:last-child{
    text-align:right;
    padding-right:8px;
}

.total-main{
    border-top:1px solid #e9edf2;
    padding-top:7px;
    margin-top:1px;
}

.total-main td{
    font-size:24px;
    font-weight:700;
    color:#111827;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
}

.total-paid td{
    color:#009966;
    font-size:13px;
}

/* =========================================
   FOOTER
========================================= */

.invoice-footer{
    margin-top:8px;
    background:{{ $invoiceFooterColor }};
    padding:10px 18px;
}

.footer-title{
    font-size:14px;
    font-weight:800;
    margin-bottom:3px;
    color:#111827;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    line-height:1.05;
}

.footer-text{
    font-size:14px;
    line-height:1.02;
    color:#1f2937;
}

.footer-line{
    margin:0 0 1px 0;
}

.footer-line:last-child{
    margin-bottom:0;
}

/* =========================================
   AVOID PDF BUGS
========================================= */

tr{
    page-break-inside: avoid;
}

.invoice,
.invoice-top,
.invoice-body,
.invoice-footer{
    page-break-inside: avoid;
}

.invoice-footer{
    page-break-before:auto;
}
</style>
</head>

<body>
@php
    use Illuminate\Support\Facades\File;
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    $s = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
    $cliente = $factura;
    if (!empty($factura['cliente_id'])) {
        $cDb = (new \App\Repositories\FileStore('clientes.json'))->find($factura['cliente_id']);
        if ($cDb) {
            $cliente = array_merge($cliente, $cDb);
        }
    }

    $items = $factura['items'] ?? [];
    $productosRaw = (new \App\Repositories\FileStore('productos.json'))->all();
    $productos = is_array($productosRaw) ? $productosRaw : [];
    $productosById = collect($productos)
        ->filter(fn($p) => is_array($p) && !empty($p['id']))
        ->keyBy(fn($p) => (string) $p['id']);
    $productosByName = collect($productos)
        ->filter(fn($p) => is_array($p) && !empty($p['nombre']))
        ->keyBy(fn($p) => strtolower(trim((string) $p['nombre'])));
    $subtotal = $factura['subtotal'] ?? collect($items)->sum(fn($i) => (float) ($i['cantidad'] ?? 0) * (float) ($i['precio'] ?? 0));
    $taxRate = (float) ($factura['tax_rate'] ?? ($s['tax_rate'] ?? 0));
    $impuestos = round($subtotal * ($taxRate / 100), 2);
    $total = round($subtotal + $impuestos, 2);

    $pagos = $factura['pagos'] ?? [];
    $pagado = round(collect($pagos)->sum(fn($p) => (float) ($p['monto'] ?? 0)), 2);
    $saldo = max(0, round($total - $pagado, 2));

    $base = $s['base_currency'] ?? 'USD';
    $monedaCode = $factura['moneda'] ?? $base;
    $decimals = !empty($s['show_decimals']) ? (int) ($s['decimal_places'] ?? 2) : 0;
    $symbol = currency_symbol($monedaCode);
    $fmt = fn($n) => $symbol . number_format((float) $n, $decimals, ',', '.');

    $logoPathRel = !empty($s['invoice_logo']) ? $s['invoice_logo'] : ($s['logo_large'] ?? ($s['logo'] ?? null));
    $logoSrc = app_public_asset_data_uri($logoPathRel) ?: app_public_asset_url($logoPathRel);

    $clienteNombre = $cliente['empresa'] ?? $cliente['nombre'] ?? $cliente['contacto_nombre'] ?? $factura['cliente'] ?? 'Cliente';
    $clienteNit = $cliente['nit'] ?? $cliente['identificacion'] ?? null;
    $clienteEmail = $cliente['contacto_email'] ?? $cliente['email'] ?? null;
    $clienteTelefono = $cliente['contacto_telefono'] ?? $cliente['telefono'] ?? null;
    $clienteDireccion = $cliente['direccion'] ?? null;
    $clienteCiudad = $cliente['ciudad'] ?? $cliente['ubicacion'] ?? null;

    $fecha = !empty($factura['fecha'])
        ? Carbon::parse($factura['fecha'])->locale('es')->translatedFormat('d F Y')
        : Carbon::now()->locale('es')->translatedFormat('d F Y');
    $vence = !empty($factura['vencimiento'])
        ? Carbon::parse($factura['vencimiento'])->locale('es')->translatedFormat('d F Y')
        : '—';

    $estadoRaw = $factura['estado'] ?? 'Pendiente';
    $estadoText = match ($estadoRaw) {
        'Pagada' => 'Pagada',
        'Vencida' => 'Vencida',
        'En borrador' => 'Borrador',
        default => ($saldo <= 0.01 ? 'Pagada' : ($pagado > 0.01 ? 'Parcial' : 'Pendiente')),
    };

    $badgeClass = $estadoText === 'Vencida'
        ? 'badge badge-overdue'
        : ($estadoText === 'Pendiente' ? 'badge badge-pending' : ($estadoText === 'Parcial' ? 'badge badge-partial' : 'badge'));

    $notesParts = array_filter([
        $factura['notas'] ?? null,
        $s['invoice_terms'] ?? null,
        $s['invoice_footer'] ?? null,
    ], fn($value) => trim((string) $value) !== '');
    $notes = implode("\n", $notesParts);
    $notesLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', (string) $notes), fn($line) => trim($line) !== ''));

    $companyName = $s['company_name'] ?? 'Datos Mi empresa';
    $companyTax = !empty($s['company_tax_id']) ? (($s['company_tax_label'] ?? 'NIT') . ': ' . $s['company_tax_id']) : null;
    $companyMain = $companyTax ? ($companyName . ' / ' . $companyTax) : $companyName;
    $companyLines = array_values(array_filter([
        $companyMain,
        $s['company_phone_number'] ?? 'Celular',
        $s['mail_from_address'] ?? ($s['email_from'] ?? 'Correo'),
        $s['company_address'] ?? 'Dirección',
    ], fn($line) => trim((string) $line) !== ''));

    $clientLines = array_values(array_filter([
        $clienteDireccion,
        $clienteCiudad,
        $clienteNit ? (($s['company_tax_label'] ?? 'ID') . ': ' . $clienteNit) : null,
        $clienteTelefono,
        $clienteEmail,
    ], fn($line) => trim((string) $line) !== ''));
@endphp

<div class="invoice-page">

    <div class="invoice">

        <!-- HEADER -->
        <div class="invoice-top">
            <table class="header-table">
                <tr>
                    <td class="header-left">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" class="company-logo" alt="Logo">
                        @else
                            <div class="company-title">
                                <span class="green">Info</span><span class="white">cus</span>
                                <small>CRM</small>
                            </div>
                        @endif
                        <div class="company-info">
                            @if(!empty($companyLines))
                                <div class="company-info-line company-main-line">{{ $companyLines[0] }}</div>
                                @foreach(array_slice($companyLines, 1) as $line)
                                    <div class="company-info-line">{{ $line }}</div>
                                @endforeach
                            @endif
                        </div>
                    </td>
                    <td class="header-right">
                        <div class="header-right-stack">
                            <div class="invoice-number">
                                {{ !empty($factura['numero']) ? $factura['numero'] : ('INV-' . Str::upper(substr((string) ($factura['id'] ?? '0000'), -4))) }}
                            </div>
                            <div class="total-top">
                                Total:
                                <span>{{ $fmt($total) }}</span>
                            </div>
                            <div class="{{ $badgeClass }}">
                                {{ Str::upper($estadoText) }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- INFO -->
        <div class="invoice-info">
            <table class="info-table">
                <tr>
                    <td class="info-col info-col-left">
                        <div class="label">Facturar a</div>
                        <div class="client-name">{{ $clienteNombre }}</div>
                        <div class="client-address">
                            @foreach($clientLines as $line)
                                <div class="client-address-line">{{ $line }}</div>
                            @endforeach
                        </div>
                    </td>
                    <td class="info-col info-col-mid">
                        <div class="label">Fecha</div>
                        <div class="info-value">{{ $fecha }}</div>
                    </td>
                    <td class="info-col info-col-right">
                        <div class="label">Vence</div>
                        <div class="info-value">{{ $vence }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- BODY -->
        <div class="invoice-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $hasQty = array_key_exists('cantidad', $item) && $item['cantidad'] !== null && $item['cantidad'] !== '';
                            $hasPrice = array_key_exists('precio', $item) && $item['precio'] !== null && $item['precio'] !== '';
                            $qty = (float) ($item['cantidad'] ?? 0);
                            $price = (float) ($item['precio'] ?? 0);
                            $lineTotal = $qty * $price;
                        @endphp
                        <tr>
                            <td>
                                @php
                                    $rawDesc = (string) ($item['descripcion'] ?? 'Servicio');
                                    $lines = preg_split('/\r\n|\r|\n/', $rawDesc) ?: [];
                                    $title = trim((string) array_shift($lines));
                                    $extraManual = trim(implode("\n", array_filter($lines, fn($ln) => trim((string) $ln) !== '')));
                                    $productDetail = trim((string) ($item['detalle'] ?? ''));
                                    if ($productDetail === '') {
                                        $productId = (string) ($item['producto_id'] ?? '');
                                        $matchedProduct = null;
                                        if ($productId !== '') {
                                            $matchedProduct = $productosById->get($productId);
                                        }
                                        if (!$matchedProduct) {
                                            $nameKey = strtolower(trim((string) ($item['descripcion'] ?? '')));
                                            if ($nameKey !== '') {
                                                $matchedProduct = $productosByName->get($nameKey);
                                            }
                                        }
                                        $productDetail = trim((string) (($matchedProduct['descripcion'] ?? '')));
                                    }
                                    $detailBlock = trim(implode("\n", array_filter([$productDetail, $extraManual], fn($ln) => trim((string) $ln) !== '')));
                                @endphp
                                <div style="font-weight:600;color:#1e293b;">{{ $title !== '' ? $title : 'Servicio' }}</div>
                                @if($detailBlock !== '')
                                    <div style="margin-top:2px;font-size:11px;line-height:1.2;color:#64748b;">{!! nl2br(e($detailBlock)) !!}</div>
                                @endif
                            </td>
                            <td>{{ $hasQty ? number_format($qty, $qty == (int) $qty ? 0 : 2, ',', '.') : '' }}</td>
                            <td>{{ $hasPrice ? $fmt($price) : '' }}</td>
                            <td>{{ ($hasQty && $hasPrice) ? $fmt($lineTotal) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td>Servicio</td>
                            <td>1</td>
                            <td>{{ $fmt($total) }}</td>
                            <td>{{ $fmt($total) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- TOTALS -->
            <div class="totals-wrapper">
                <div class="totals">
                    <div class="total-row">
                        <table class="total-table">
                            <tr>
                                <td>Subtotal</td>
                                <td>{{ $fmt($subtotal) }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="total-row">
                        <table class="total-table">
                            <tr>
                                <td>{{ $s['tax_name'] ?? 'Impuestos' }} ({{ rtrim(rtrim(number_format($taxRate, 2, ',', '.'), '0'), ',') }}%)</td>
                                <td>{{ $fmt($impuestos) }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="total-row total-main">
                        <table class="total-table">
                            <tr>
                                <td>Total</td>
                                <td>{{ $fmt($total) }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($pagado > 0)
                    <div class="total-row total-paid">
                        <table class="total-table">
                            <tr>
                                <td>Pagado</td>
                                <td>- {{ $fmt($pagado) }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="invoice-footer">
            <div class="footer-title">Información de Pago / Notas</div>
            <div class="footer-text">
                @if($notes)
                    @foreach($notesLines as $line)
                        <div class="footer-line">{{ $line }}</div>
                    @endforeach
                @else
                    @if(!empty($s['invoice_bank_details']))
                        @foreach(array_values(array_filter(preg_split('/\r\n|\r|\n/', (string) $s['invoice_bank_details']), fn($line) => trim($line) !== '')) as $line)
                            <div class="footer-line">{{ $line }}</div>
                        @endforeach
                    @else
                        Gracias por su preferencia.
                    @endif
                @endif
            </div>
        </div>

    </div>
</div>

</body>
</html>
