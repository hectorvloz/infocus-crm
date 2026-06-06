<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportesController extends Controller
{
    protected $facturas;
    protected $gastos;
    protected $proyectos;
    protected $leads;
    protected $proveedores;

    public function __construct()
    {
        $this->facturas = new FileStore('facturas.json');
        $this->gastos = new FileStore('gastos.json');
        $this->proyectos = new FileStore('proyectos.json');
        $this->leads = new FileStore('leads.json');
        $this->proveedores = new FileStore('proveedores.json');
    }

    public function index(Request $request)
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'COP'));

        $facturas = collect($this->facturas->all() ?: []);
        $gastos = collect($this->gastos->all() ?: []);
        $proyectos = collect($this->proyectos->all() ?: []);
        $leads = collect($this->leads->all() ?: []);
        $proveedores = collect($this->proveedores->all() ?: []);
        $proveedoresMap = $proveedores->keyBy('id');

        $yearSet = [];
        foreach ($facturas as $factura) {
            foreach (($factura['pagos'] ?? []) as $pago) {
                $d = $this->safeParseDate($pago['fecha'] ?? null);
                if ($d) {
                    $yearSet[$d->year] = true;
                }
            }
            if (empty($factura['pagos']) && ($factura['estado'] ?? '') === 'Pagada') {
                $d = $this->safeParseDate($factura['fecha'] ?? null);
                if ($d) {
                    $yearSet[$d->year] = true;
                }
            }
        }
        foreach ($gastos as $gasto) {
            $d = $this->safeParseDate($gasto['fecha'] ?? null);
            if ($d) {
                $yearSet[$d->year] = true;
            }
        }

        if (empty($yearSet)) {
            $yearSet[(int) now()->year] = true;
        }

        $years = array_keys($yearSet);
        rsort($years);
        $defaultYear = (string) ($years[0] ?? now()->year);

        $selectedYear = (string) $request->query('year', $defaultYear);
        if (!in_array((int) $selectedYear, $years, true)) {
            $selectedYear = $defaultYear;
        }

        $selectedMonth = (string) $request->query('month', 'all');
        if ($selectedMonth !== 'all') {
            $selectedMonth = str_pad((string) ((int) $selectedMonth), 2, '0', STR_PAD_LEFT);
            if ((int) $selectedMonth < 1 || (int) $selectedMonth > 12) {
                $selectedMonth = 'all';
            }
        }

        $paidMovements = $facturas->flatMap(function ($factura) use ($baseCurrency) {
            $cliente = (string) ($factura['cliente'] ?? 'Cliente sin nombre');
            $movements = collect($factura['pagos'] ?? [])->map(function ($pago) use ($factura, $baseCurrency, $cliente) {
                $date = $this->safeParseDate($pago['fecha'] ?? null);
                if (!$date) {
                    return null;
                }
                return [
                    'date' => $date,
                    'cliente' => $cliente,
                    'monto' => $this->resolvePaymentBaseAmount($factura, $pago, $baseCurrency),
                ];
            })->filter();

            if ($movements->isEmpty() && ($factura['estado'] ?? '') === 'Pagada') {
                $date = $this->safeParseDate($factura['fecha'] ?? null);
                if ($date) {
                    $movements->push([
                        'date' => $date,
                        'cliente' => $cliente,
                        'monto' => $this->resolveInvoiceBaseAmount($factura, $baseCurrency),
                    ]);
                }
            }

            return $movements;
        })->values();

        $filteredIncomeMovements = $paidMovements->filter(function ($row) use ($selectedYear, $selectedMonth) {
            return $this->matchesPeriod($row['date'], $selectedYear, $selectedMonth);
        })->values();

        $filteredExpenses = $gastos->filter(function ($gasto) use ($selectedYear, $selectedMonth) {
            $date = $this->safeParseDate($gasto['fecha'] ?? null);
            return $date && $this->matchesPeriod($date, $selectedYear, $selectedMonth);
        })->values();

        $totalIngresos = round($filteredIncomeMovements->sum('monto'), 2);
        $totalGastos = round($filteredExpenses->sum(fn($g) => $this->resolveExpenseBaseAmount($g, $baseCurrency)), 2);
        $balance = $totalIngresos - $totalGastos;

        $months = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
        ];
        $lineLabels = array_values($months);
        $lineIncome = [];
        $lineExpenses = [];
        foreach (array_keys($months) as $monthNum) {
            $monthIncome = $paidMovements
                ->filter(fn($row) => $this->matchesPeriod($row['date'], $selectedYear, $monthNum))
                ->sum('monto');

            $monthExpense = $gastos
                ->filter(function ($gasto) use ($selectedYear, $monthNum) {
                    $date = $this->safeParseDate($gasto['fecha'] ?? null);
                    return $date && $this->matchesPeriod($date, $selectedYear, $monthNum);
                })
                ->sum(fn($g) => $this->resolveExpenseBaseAmount($g, $baseCurrency));

            $lineIncome[] = round((float) $monthIncome, 2);
            $lineExpenses[] = round((float) $monthExpense, 2);
        }

        $gastosPorProveedor = $filteredExpenses->groupBy(function ($g) use ($proveedoresMap) {
            $pid = (string) ($g['proveedor_id'] ?? '');
            if ($pid !== '' && isset($proveedoresMap[$pid])) {
                return (string) ($proveedoresMap[$pid]['nombre'] ?? 'Proveedor');
            }
            return 'Sin proveedor';
        })->map(function ($rows) use ($baseCurrency) {
            return round($rows->sum(fn($g) => $this->resolveExpenseBaseAmount($g, $baseCurrency)), 2);
        })->sortDesc()
            ->take(5);

        $clientesTop = $filteredIncomeMovements
            ->groupBy('cliente')
            ->map(fn($rows) => round($rows->sum('monto'), 2))
            ->sortDesc()
            ->take(5);

        $proyectosFiltrados = $proyectos->filter(function ($p) use ($selectedYear, $selectedMonth) {
            if (!empty($p['archived']) && ($p['archived'] === true || $p['archived'] === '1' || $p['archived'] === 1)) {
                return false;
            }
            $date = $this->safeParseDate($p['created_at'] ?? $p['fecha_inicio'] ?? $p['vencimiento'] ?? null);
            if (!$date) {
                return true;
            }
            return $this->matchesPeriod($date, $selectedYear, $selectedMonth);
        })->values();
        if ($proyectosFiltrados->isEmpty()) {
            $proyectosFiltrados = $proyectos->values();
        }
        $proyectosStatus = $proyectosFiltrados
            ->groupBy(fn($p) => trim((string) ($p['etapa'] ?? 'Sin etapa')) ?: 'Sin etapa')
            ->map->count()
            ->sortDesc();

        $leadsFiltrados = $leads->filter(function ($lead) use ($selectedYear, $selectedMonth) {
            $date = $this->safeParseDate($lead['created_at'] ?? null);
            if (!$date) {
                return true;
            }
            return $this->matchesPeriod($date, $selectedYear, $selectedMonth);
        });
        $leadsStatus = $leadsFiltrados
            ->groupBy(fn($l) => trim((string) ($l['etapa'] ?? 'Sin etapa')) ?: 'Sin etapa')
            ->map->count()
            ->sortDesc();

        $monthOptions = collect($months)->map(function ($label, $value) {
            return ['value' => $value, 'label' => $label];
        })->values()->all();

        $periodLabel = $selectedMonth === 'all'
            ? 'Año ' . $selectedYear
            : ($months[$selectedMonth] ?? $selectedMonth) . ' ' . $selectedYear;

        $chartIncomeExpense = [
            'labels' => $lineLabels,
            'income' => $lineIncome,
            'expenses' => $lineExpenses,
        ];

        $chartExpenseSupplier = [
            'labels' => $gastosPorProveedor->keys()->values()->all(),
            'values' => $gastosPorProveedor->values()->all(),
        ];

        $chartTopClients = [
            'labels' => $clientesTop->keys()->values()->all(),
            'values' => $clientesTop->values()->all(),
        ];

        $chartProjectStatus = [
            'labels' => $proyectosStatus->keys()->values()->all(),
            'values' => $proyectosStatus->values()->all(),
        ];

        return view('reportes.index', compact(
            'baseCurrency',
            'years',
            'selectedYear',
            'selectedMonth',
            'monthOptions',
            'periodLabel',
            'totalIngresos',
            'totalGastos',
            'balance',
            'gastosPorProveedor', 
            'clientesTop',
            'proyectosStatus',
            'leadsStatus',
            'chartIncomeExpense',
            'chartExpenseSupplier',
            'chartTopClients',
            'chartProjectStatus'
        ));
    }

    protected function safeParseDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function matchesPeriod(Carbon $date, string $year, string $month): bool
    {
        if ($date->format('Y') !== $year) {
            return false;
        }
        if ($month === 'all') {
            return true;
        }
        return $date->format('m') === $month;
    }

    protected function resolveInvoiceBaseAmount(array $invoice, string $baseCurrency): float
    {
        $total = (float) ($invoice['total'] ?? 0);
        $totalBase = (float) ($invoice['total_base'] ?? 0);
        if ($totalBase > 0) {
            return $totalBase;
        }

        $invoiceCurrency = strtoupper((string) ($invoice['moneda'] ?? $baseCurrency));
        if ($invoiceCurrency === $baseCurrency) {
            return $total;
        }

        $rate = (float) ($invoice['tasa'] ?? 0);
        return $rate > 0 ? round($total * $rate, 2) : $total;
    }

    protected function resolvePaymentBaseAmount(array $invoice, array $payment, string $baseCurrency): float
    {
        if (isset($payment['monto_base'])) {
            return (float) $payment['monto_base'];
        }

        $paymentAmount = (float) ($payment['monto'] ?? 0);
        if ($paymentAmount <= 0) {
            return 0;
        }

        $invoiceTotal = (float) ($invoice['total'] ?? 0);
        $invoiceBase = $this->resolveInvoiceBaseAmount($invoice, $baseCurrency);
        if ($invoiceTotal > 0 && $invoiceBase > 0) {
            $factor = $invoiceBase / $invoiceTotal;
            // Heuristica: algunos pagos ya vienen en base.
            return $paymentAmount <= ($invoiceTotal * 1.2)
                ? round($paymentAmount * $factor, 2)
                : $paymentAmount;
        }

        return $paymentAmount;
    }

    protected function resolveExpenseBaseAmount(array $expense, string $baseCurrency): float
    {
        if (isset($expense['monto_base'])) {
            return (float) $expense['monto_base'];
        }

        $amount = (float) ($expense['monto'] ?? 0);
        $currency = strtoupper((string) ($expense['moneda'] ?? $baseCurrency));
        if ($currency === $baseCurrency) {
            return $amount;
        }

        $rate = (float) ($expense['tasa'] ?? 0);
        return $rate > 0 ? round($amount * $rate, 2) : $amount;
    }

    public function export(Request $request)
    {
        $type = $request->query('type', 'financiero');
        $format = strtolower((string) $request->query('format', 'excel'));
        $year = (string) $request->query('year', date('Y'));
        $month = (string) $request->query('month', 'all');

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'COP'));

        $facturas = collect($this->facturas->all() ?: []);
        $gastos = collect($this->gastos->all() ?: []);
        $proyectos = collect($this->proyectos->all() ?: []);
        $leads = collect($this->leads->all() ?: []);
        $proveedores = collect($this->proveedores->all() ?: [])->keyBy('id');

        $incomeMovements = $facturas->flatMap(function ($factura) use ($baseCurrency) {
            $rows = collect($factura['pagos'] ?? [])->map(function ($pago) use ($factura, $baseCurrency) {
                $date = $this->safeParseDate($pago['fecha'] ?? null);
                if (!$date) {
                    return null;
                }
                return [
                    'fecha' => $date->format('Y-m-d'),
                    'cliente' => (string) ($factura['cliente'] ?? 'Cliente'),
                    'monto' => $this->resolvePaymentBaseAmount($factura, $pago, $baseCurrency),
                ];
            })->filter();

            if ($rows->isEmpty() && ($factura['estado'] ?? '') === 'Pagada') {
                $date = $this->safeParseDate($factura['fecha'] ?? null);
                if ($date) {
                    $rows->push([
                        'fecha' => $date->format('Y-m-d'),
                        'cliente' => (string) ($factura['cliente'] ?? 'Cliente'),
                        'monto' => $this->resolveInvoiceBaseAmount($factura, $baseCurrency),
                    ]);
                }
            }

            return $rows;
        })->filter(function ($row) use ($year, $month) {
            $d = $this->safeParseDate($row['fecha'] ?? null);
            return $d && $this->matchesPeriod($d, $year, $month);
        })->values();

        $expenseRows = $gastos->map(function ($g) use ($proveedores, $baseCurrency) {
            $pid = (string) ($g['proveedor_id'] ?? '');
            return [
                'fecha' => (string) ($g['fecha'] ?? ''),
                'concepto' => (string) ($g['concepto'] ?? ''),
                'categoria' => (string) ($g['categoria'] ?? ''),
                'proveedor' => $pid !== '' && isset($proveedores[$pid]) ? (string) ($proveedores[$pid]['nombre'] ?? 'Proveedor') : 'Sin proveedor',
                'monto' => $this->resolveExpenseBaseAmount($g, $baseCurrency),
            ];
        })->filter(function ($g) use ($year, $month) {
            $d = $this->safeParseDate($g['fecha'] ?? null);
            return $d && $this->matchesPeriod($d, $year, $month);
        })->values();

        $projectRows = $proyectos->map(function ($p) {
            return [
                'proyecto' => (string) ($p['titulo'] ?? $p['nombre'] ?? 'Proyecto'),
                'etapa' => (string) ($p['etapa'] ?? 'Sin etapa'),
                'progreso' => (float) ($p['progreso'] ?? 0),
                'vencimiento' => (string) ($p['vencimiento'] ?? ''),
                'created_at' => (string) ($p['created_at'] ?? ''),
            ];
        })->filter(function ($p) use ($year, $month) {
            $d = $this->safeParseDate($p['created_at'] ?: $p['vencimiento']);
            if (!$d) {
                return true;
            }
            return $this->matchesPeriod($d, $year, $month);
        })->values();

        $leadsSummary = $leads
            ->filter(function ($lead) use ($year, $month) {
                $d = $this->safeParseDate($lead['created_at'] ?? null);
                if (!$d) {
                    return true;
                }
                return $this->matchesPeriod($d, $year, $month);
            })
            ->groupBy(fn($l) => trim((string) ($l['etapa'] ?? 'Sin etapa')) ?: 'Sin etapa')
            ->map->count()
            ->sortDesc();

        if ($format === 'pdf' && class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.export_pdf', [
                'type' => $type,
                'year' => $year,
                'month' => $month,
                'baseCurrency' => $baseCurrency,
                'incomeMovements' => $incomeMovements,
                'expenseRows' => $expenseRows,
                'projectRows' => $projectRows,
                'leadsSummary' => $leadsSummary,
            ])->setPaper('a4', 'portrait');

            return $pdf->download('reporte_' . $type . '_' . date('Y-m-d') . '.pdf');
        }

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_' . $type . '_' . date('Y-m-d') . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($type, $incomeMovements, $expenseRows, $projectRows, $leadsSummary, $baseCurrency) {
            $out = fopen('php://output', 'w');

            if ($type === 'financiero') {
                fputcsv($out, ['SECCION', 'FINANCIERO'], "\t");
                fputcsv($out, ['Fecha', 'Tipo', 'Detalle', 'Monto (' . $baseCurrency . ')'], "\t");
                foreach ($incomeMovements as $row) {
                    fputcsv($out, [$row['fecha'], 'Ingreso', $row['cliente'], $row['monto']], "\t");
                }
                foreach ($expenseRows as $row) {
                    fputcsv($out, [$row['fecha'], 'Gasto', $row['concepto'], -1 * (float) $row['monto']], "\t");
                }
            } elseif ($type === 'proveedores') {
                fputcsv($out, ['SECCION', 'GASTO POR PROVEEDOR'], "\t");
                fputcsv($out, ['Proveedor', 'Concepto', 'Categoria', 'Fecha', 'Monto (' . $baseCurrency . ')'], "\t");
                foreach ($expenseRows as $row) {
                    fputcsv($out, [$row['proveedor'], $row['concepto'], $row['categoria'], $row['fecha'], $row['monto']], "\t");
                }
            } else {
                fputcsv($out, ['SECCION', 'PROYECTOS Y LEADS'], "\t");
                fputcsv($out, ['Proyecto', 'Etapa', 'Progreso', 'Vencimiento'], "\t");
                foreach ($projectRows as $row) {
                    fputcsv($out, [$row['proyecto'], $row['etapa'], $row['progreso'] . '%', $row['vencimiento']], "\t");
                }
                fputcsv($out, [], "\t");
                fputcsv($out, ['Etapa lead', 'Cantidad'], "\t");
                foreach ($leadsSummary as $stage => $count) {
                    fputcsv($out, [$stage, $count], "\t");
                }
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
