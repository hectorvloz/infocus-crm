<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11.5px; background: #fff; }

        /* Header */
        .pdf-header { background: #ecfe88; border-bottom: 3px solid #bce000; padding: 18px 24px 14px; }
        .pdf-header h1 { font-size: 20px; font-weight: 700; color: #1e293b; letter-spacing: -0.3px; }
        .pdf-header .subtitle { font-size: 11px; color: #475569; margin-top: 4px; }
        .pdf-meta { display: flex; gap: 20px; margin-top: 10px; }
        .pdf-meta .badge { background: rgba(0,0,0,0.08); border-radius: 6px; padding: 3px 10px; font-size: 10px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.06em; }

        /* Body */
        .pdf-body { padding: 20px 24px; }

        /* KPI row (financiero) */
        .kpi-row { display: table; width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 18px; }
        .kpi-cell { display: table-cell; width: 33%; border-radius: 10px; padding: 12px 14px; }
        .kpi-income { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .kpi-expense { background: #fff1f2; border: 1px solid #fecdd3; }
        .kpi-balance { background: #f8fafc; border: 1px solid #e2e8f0; }
        .kpi-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 4px; }
        .kpi-value { font-size: 16px; font-weight: 700; }
        .kpi-income .kpi-value { color: #16a34a; }
        .kpi-expense .kpi-value { color: #dc2626; }
        .kpi-balance .kpi-value { color: #0f172a; }

        /* Section heading */
        .section-title { font-size: 12px; font-weight: 700; color: #0f172a; padding-bottom: 5px; border-bottom: 2px solid #ecfe88; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 0.06em; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #1e293b; }
        thead th { color: #f1f5f9; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 7px 10px; text-align: left; }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:nth-child(odd) { background: #fff; }
        tbody td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tbody td.right { text-align: right; font-weight: 600; }

        /* Row color codes */
        .row-income td { border-left: 3px solid #22c55e; }
        .row-expense td { border-left: 3px solid #ef4444; }
        .row-expense td.right { color: #dc2626; }
        .row-income td.right { color: #16a34a; }

        /* Proveedor table specific */
        .prov-amount { color: #dc2626; font-weight: 700; }

        /* Project status badges */
        .badge-status { display: inline-block; padding: 2px 7px; border-radius: 5px; font-size: 9.5px; font-weight: 700; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-completed { background: #dbeafe; color: #1e40af; }
        .badge-paused { background: #fef9c3; color: #854d0e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        /* Footer */
        .pdf-footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 9.5px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

<div class="pdf-header">
    <h1>Reporte &mdash; {{ ucfirst($type) }}</h1>
    <div class="subtitle">Documento generado el {{ now()->format('d/m/Y H:i') }}</div>
    <div class="pdf-meta">
        <span class="badge">Periodo: {{ $month === 'all' ? 'Todo el año '.$year : date('F', mktime(0,0,0,$month,1)).' '.$year }}</span>
        <span class="badge">Moneda: {{ $baseCurrency }}</span>
    </div>
</div>

<div class="pdf-body">

@if($type === 'financiero')
    {{-- KPI Summary --}}
    @php
        $totalInc = collect($incomeMovements)->sum(fn($r) => (float)($r['monto'] ?? 0));
        $totalExp = collect($expenseRows)->sum(fn($r) => (float)($r['monto'] ?? 0));
        $bal = $totalInc - $totalExp;
    @endphp
    <table class="kpi-row" style="margin-bottom:18px;">
        <tr>
            <td class="kpi-cell kpi-income">
                <div class="kpi-label">Ingresos</div>
                <div class="kpi-value">{{ number_format($totalInc, 2) }} {{ $baseCurrency }}</div>
            </td>
            <td class="kpi-cell kpi-expense">
                <div class="kpi-label">Gastos</div>
                <div class="kpi-value">{{ number_format($totalExp, 2) }} {{ $baseCurrency }}</div>
            </td>
            <td class="kpi-cell kpi-balance">
                <div class="kpi-label">Balance neto</div>
                <div class="kpi-value" style="color: {{ $bal >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($bal, 2) }} {{ $baseCurrency }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Cobros recibidos</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th class="right">Monto ({{ $baseCurrency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incomeMovements as $row)
                <tr class="row-income">
                    <td>{{ $row['fecha'] }}</td>
                    <td>{{ $row['cliente'] }}</td>
                    <td class="right">+ {{ number_format((float) $row['monto'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="color:#94a3b8;padding:10px;">Sin cobros en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Gastos ejecutados</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th class="right">Monto ({{ $baseCurrency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenseRows as $row)
                <tr class="row-expense">
                    <td>{{ $row['fecha'] }}</td>
                    <td>{{ $row['concepto'] }}</td>
                    <td class="right">&minus; {{ number_format((float) $row['monto'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="color:#94a3b8;padding:10px;">Sin gastos en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

@elseif($type === 'proveedores')
    @php
        $totalProv = collect($expenseRows)->sum(fn($r) => (float)($r['monto'] ?? 0));
    @endphp
    <div style="background:#fff1f2;border:1px solid #fecdd3;border-radius:8px;padding:10px 14px;margin-bottom:16px;display:inline-block;">
        <div class="kpi-label">Total gastado en proveedores</div>
        <div class="kpi-value" style="color:#dc2626;font-size:17px;">{{ number_format($totalProv, 2) }} {{ $baseCurrency }}</div>
    </div>

    <div class="section-title">Detalle de gastos por proveedor</div>
    <table>
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Concepto</th>
                <th>Categoría</th>
                <th>Fecha</th>
                <th class="right">Monto ({{ $baseCurrency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenseRows as $row)
                <tr>
                    <td><strong>{{ $row['proveedor'] }}</strong></td>
                    <td>{{ $row['concepto'] }}</td>
                    <td>{{ $row['categoria'] }}</td>
                    <td>{{ $row['fecha'] }}</td>
                    <td class="right prov-amount">&minus; {{ number_format((float) $row['monto'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#94a3b8;padding:10px;">Sin gastos para el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

@else
    <div class="section-title">Proyectos del periodo</div>
    <table>
        <thead>
            <tr>
                <th>Proyecto</th>
                <th>Etapa</th>
                <th class="right">Progreso</th>
                <th>Vencimiento</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projectRows as $row)
                @php
                    $etapa = strtolower($row['etapa'] ?? '');
                    $badgeClass = str_contains($etapa,'complet') ? 'badge-completed' : (str_contains($etapa,'activ') ? 'badge-active' : (str_contains($etapa,'paus') ? 'badge-paused' : 'badge-cancelled'));
                @endphp
                <tr>
                    <td>{{ $row['proyecto'] }}</td>
                    <td><span class="badge-status {{ $badgeClass }}">{{ $row['etapa'] }}</span></td>
                    <td class="right">{{ $row['progreso'] }}%</td>
                    <td>{{ $row['vencimiento'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:#94a3b8;padding:10px;">Sin proyectos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Pipeline de leads</div>
    <table>
        <thead>
            <tr>
                <th>Etapa</th>
                <th class="right">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leadsSummary as $stage => $count)
                <tr>
                    <td>{{ $stage }}</td>
                    <td class="right"><strong>{{ $count }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="2" style="color:#94a3b8;padding:10px;">Sin leads.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif

</div>

<div class="pdf-footer">
    Reporte generado por InFocus CRM &bull; {{ now()->format('d/m/Y') }} &bull; Confidencial
</div>

</body>
</html>
