<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cotización {{ $cotizacion['numero'] }}</title>
@include('partials.favicon')

<style>
@page { size: A4; margin: 20mm; }

body{
    font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    margin:0;
    background:#f5f5f5;
    color:#111;
}

.wrap{
    max-width:900px;
    margin:auto;
    background:#fff;
    padding:30px;
}

.header{
    background:#000;
    color:#fff;
    padding:30px;
    border-radius:12px;
}

.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logo img{
    height:50px;
}

.invoice-title{
    font-size:40px;
    font-weight:800;
    letter-spacing:2px;
}

.meta{
    margin-top:10px;
    font-size:14px;
}

.client-box{
    margin-top:25px;
    display:flex;
    justify-content:space-between;
}

.client-info{
    font-size:14px;
    line-height:1.6;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:30px;
}

th{
    background:#000;
    color:#fff;
    padding:12px;
    font-size:13px;
    text-align:left;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
    font-size:14px;
}

.num{
    text-align:right !important;
}

.totals{
    width:40%;
    margin-left:auto;
    margin-top:25px;
    padding-right: 12px;
}

.totals div{
    display:flex;
    justify-content:space-between;
    padding:8px 0;
}

.total-final{
    font-weight:800;
    font-size:18px;
    background:#ecfe88;
    color:#000;
    padding:12px;
    border-radius:8px;
    margin-top:10px;
    border-top:none;
}

.footer{
    margin-top:40px;
    font-size:13px;
    border-top:1px solid #ddd;
    padding-top:15px;
    display:flex;
    justify-content:space-between;
}

.print-btn{
    margin:20px;
    text-align:right;
}

.print-btn button{
    padding:10px 18px;
    border:none;
    background:#000;
    color:#fff;
    border-radius:20px;
    font-weight:600;
    cursor:pointer;
}

@media print{
    .print-btn{ display:none; }
    body{ background:#fff; }
}
</style>
</head>
<body>

@php
$s = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
$items = $cotizacion['items'] ?? [];

$subtotal = collect($items)->sum(fn($i)=>($i['cantidad'] ?? 0)*($i['precio'] ?? 0));
$impuestos = $cotizacion['impuestos'] ?? round($subtotal * 0.16,2);
$total = round($subtotal + $impuestos,2);

$base = $s['base_currency'] ?? 'USD';
$moneda = $cotizacion['moneda'] ?? $base;
@endphp

<div class="print-btn">
    <button onclick="window.print()">Imprimir</button>
</div>

<div class="wrap">

    <div class="header">
        <div class="top">
            <div class="invoice-title">COTIZACIÓN</div>
            <div class="logo">
                @if(!empty($s['logo']))
                    <img src="{{ $s['logo'] }}">
                @endif
            </div>
        </div>

        <div class="meta">
            Cotización #: {{ $cotizacion['numero'] ?? '' }} <br>
            Fecha: {{ \Carbon\Carbon::parse($cotizacion['fecha'])->format('d/m/Y') }}
        </div>

        <div class="client-box">
            <div class="client-info">
                <strong>Para:</strong><br>
                {{ $cotizacion['cliente'] ?? '' }}<br>
                <!-- Manual client data doesn't have address fields yet -->
            </div>

            <div class="client-info" style="text-align:right;">
                <strong>{{ $s['company_name'] ?? 'Mi Empresa' }}</strong><br>
                {{ $s['company_address'] ?? '' }}<br>
                {{ $s['email_from'] ?? '' }}<br>
                {{ $s['whatsapp_number'] ?? '' }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="num">Precio</th>
                <th class="num">Cant.</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $it)
            <tr>
                <td>{{ $it['descripcion'] ?? '' }}</td>
                <td class="num">{{ $moneda }} {{ number_format($it['precio'],2) }}</td>
                <td class="num">{{ $it['cantidad'] }}</td>
                <td class="num">
                    {{ $moneda }} {{ number_format($it['precio'] * $it['cantidad'],2) }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div>
            <span>Subtotal</span>
            <span>{{ $moneda }} {{ number_format($subtotal,2) }}</span>
        </div>
        <div>
            <span>Impuestos</span>
            <span>{{ $moneda }} {{ number_format($impuestos,2) }}</span>
        </div>
        <div class="total-final">
            <span>Total</span>
            <span>{{ $moneda }} {{ number_format($total,2) }}</span>
        </div>
    </div>

    <div class="footer">
        <div>
            Válido hasta: {{ !empty($cotizacion['vencimiento']) ? \Carbon\Carbon::parse($cotizacion['vencimiento'])->format('d/m/Y') : '15 días' }}
        </div>

        <div style="text-align:right;">
            Términos y Condiciones<br>
            Gracias por su interés.
        </div>
    </div>

</div>
</body>
</html>
