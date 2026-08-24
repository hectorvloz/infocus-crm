<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use App\Repositories\TimelineStore;
use App\Support\TemplateMail;

class FacturasController extends Controller
{
    protected FileStore $store;
    protected FileStore $clientes;
    protected TimelineStore $timeline;

    public function __construct()
    {
        $this->store = new FileStore('facturas.json');
        $this->clientes = new FileStore('clientes.json');
        $this->timeline = new TimelineStore();
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q',''));
        $estado = $request->query('estado','');
        $range = $request->query('range', 'month');
        $month = $request->query('month', date('Y-m'));
        $navTitle = 'Mes actual';
        $navMonthLabel = $this->formatMonthLabel($month);
        $this->autoClonarRecurrencias();
        $all = collect($this->store->all());
        $templatesById = $all->keyBy(fn($f) => (string) ($f['id'] ?? ''));
        if ($range === 'all') {
            $rangeLabel = 'Todas las facturas';
            $navTitle = 'Todas';
            $navMonthLabel = 'Todas';
            $filtered = $all;
        } elseif ($range === 'month') {
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $rangeStart = date('Y-m-01', strtotime($month . '-01'));
                $rangeEnd = date('Y-m-t', strtotime($month . '-01'));
                $rangeLabel = $this->formatMonthLabel($month);
                $navTitle = 'Mes actual';
                $navMonthLabel = $this->formatMonthLabel($month);
            } else {
                [$rangeStart, $rangeEnd, $rangeLabel] = $this->resolveRange($range);
                $month = date('Y-m');
                $navTitle = 'Mes actual';
                $navMonthLabel = $this->formatMonthLabel($month);
            }
        } else {
            [$rangeStart, $rangeEnd, $rangeLabel] = $this->resolveRange($range);
            if ($range === 'prev') {
                $navTitle = 'Mes anterior';
                $navMonthLabel = $this->formatMonthLabel(date('Y-m', strtotime('first day of last month')));
            } elseif ($range === '6m') {
                $navTitle = 'Ultimos 6 meses';
                $navMonthLabel = $this->formatMonthLabel(date('Y-m'));
            } elseif ($range === 'year') {
                $navTitle = 'Ultimos 12 meses';
                $navMonthLabel = $this->formatMonthLabel(date('Y-m'));
            }
        }
        if ($range !== 'all') {
            $filtered = $all->filter(function($f) use ($rangeStart, $rangeEnd){
                $fecha = $f['fecha'] ?? null;
                if (!$fecha) return false;
                return $fecha >= $rangeStart && $fecha <= $rangeEnd;
            });
        }
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $base = $settings['base_currency'] ?? 'USD';
        $paymentMethods = $this->paymentMethodOptions();

        $effTotal = function ($f) {
            return (float) ($f['total_base'] ?? $f['total'] ?? 0);
        };
        $effPaid = function ($f) use ($effTotal, $base) {
            // Usar saldo_base guardado si está disponible (autoritativo, calculado en pagar())
            if (array_key_exists('saldo_base', $f) && $f['saldo_base'] !== null) {
                $totalBase = (float) $effTotal($f);
                $saldoBase = (float) $f['saldo_base'];
                return max(0, round($totalBase - $saldoBase, 2));
            }
            // Fallback para facturas sin saldo_base (muy antiguas)
            $invoiceTotal = (float) ($f['total'] ?? 0);
            $baseTotal = (float) $effTotal($f);
            $isForeign = (($f['moneda'] ?? $base) !== $base) && $invoiceTotal > 0 && $baseTotal > 0;
            $factor = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;

            return round(collect($f['pagos'] ?? [])->sum(function ($p) use ($isForeign, $factor, $invoiceTotal) {
                if (isset($p['monto_base'])) {
                    return (float) $p['monto_base'];
                }
                $monto = (float) ($p['monto'] ?? 0);
                if (!$isForeign) {
                    return $monto;
                }
                return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
            }), 2);
        };
        $pendingBase = function ($f) use ($effTotal, $effPaid) {
            return max(0, round($effTotal($f) - $effPaid($f), 2));
        };
        $decorate = function ($f) use ($effTotal, $effPaid, $pendingBase, $templatesById) {
            $f['_total_base'] = $effTotal($f);
            $f['_paid_base'] = $effPaid($f);
            $f['_due_base'] = $pendingBase($f);
            $rec = (array) ($f['recurrencia'] ?? []);
            $everyMonths = null;
            $recurrenceEnabled = false;
            $recurrenceTargetId = null;

            if ($this->isConfiguredRecurrence($rec)) {
                $everyMonths = max(1, (int) ($rec['every_months'] ?? 1));
                $recurrenceEnabled = !array_key_exists('enabled', $rec) || !empty($rec['enabled']);
                $recurrenceTargetId = (string) ($f['id'] ?? '');
            } elseif (!empty($f['recurrencia_origen_id'])) {
                $template = $templatesById->get((string) $f['recurrencia_origen_id']);
                $templateRec = (array) (($template['recurrencia'] ?? []) ?: []);
                if ($this->isConfiguredRecurrence($templateRec)) {
                    $everyMonths = max(1, (int) ($templateRec['every_months'] ?? 1));
                    $recurrenceEnabled = !array_key_exists('enabled', $templateRec) || !empty($templateRec['enabled']);
                    $recurrenceTargetId = (string) ($template['id'] ?? '');
                }
            }

            $f['_is_recurrente'] = $everyMonths !== null || !empty($f['recurrencia_origen_id']) || in_array(($f['origen'] ?? ''), ['recurrente', 'recurrente_preemitida'], true);
            $f['_recurrencia_enabled'] = $recurrenceEnabled;
            $f['_recurrencia_target_id'] = $recurrenceTargetId;
            $f['_recurrencia_toggleable'] = $recurrenceTargetId !== null && $recurrenceTargetId !== '';
            if ($everyMonths !== null) {
                $f['_recurrencia_label'] = $everyMonths === 1 ? 'Cada mes' : "Cada {$everyMonths} meses";
            } elseif ($f['_is_recurrente']) {
                $f['_recurrencia_label'] = 'Recurrente';
            } else {
                $f['_recurrencia_label'] = '—';
            }
            return $f;
        };

        $facturas = $filtered
            ->when($q !== '', fn($c)=>$c->filter(fn($f)=> str_contains(strtolower($f['cliente'] ?? ''), strtolower($q)) || str_contains(strtolower($f['numero'] ?? ''), strtolower($q))))
            ->when($estado !== '', function ($c) use ($estado) {
                if ($estado === 'Recurrente') {
                    return $c->filter(function ($f) {
                        $rec = (array) ($f['recurrencia'] ?? []);
                        return $this->isConfiguredRecurrence($rec)
                            || !empty($f['recurrencia_origen_id'])
                            || in_array(($f['origen'] ?? ''), ['recurrente', 'recurrente_preemitida'], true);
                    });
                }
                if ($estado === 'Pendiente') {
                    return $c->filter(fn($f) => in_array(($f['estado'] ?? ''), ['Pendiente', 'Enviada'], true));
                }
                return $c->where('estado', $estado);
            })
            ->sortByDesc('fecha')
            ->map($decorate)
            ->values()
            ->all();
        $total   = round($filtered->sum($effTotal), 2);
        $pagos   = round($filtered->sum($effPaid), 2);
        $due     = round($filtered->filter(fn($f) => in_array(($f['estado'] ?? ''), ['Pendiente', 'Enviada'], true))->sum($pendingBase), 2);
        $overdue = round($filtered->where('estado','Vencida')->sum($pendingBase), 2);

        // Breakdown por cobrar y vencido (sobre TODAS las facturas, no solo el rango)
        $hoy           = date('Y-m-d');
        $mesIni        = date('Y-m-01');
        $mesFin        = date('Y-m-t');
        $prevIni       = date('Y-m-01', strtotime('first day of last month'));
        $prevFin       = date('Y-m-t',  strtotime('last day of last month'));

        $todasEnviadas = $all->filter(fn($f) => in_array(($f['estado'] ?? ''), ['Pendiente', 'Enviada'], true));
        $receivableMonthRef = fn($f) => !empty($f['fecha']) ? $f['fecha'] : ($f['vencimiento'] ?? null);
        $dateIsCurrentMonth = fn($date) => $date && $date >= $mesIni && $date <= $mesFin;
        $dueEsteMes    = $todasEnviadas->filter(function($f) use ($dateIsCurrentMonth) {
            return $dateIsCurrentMonth($f['fecha'] ?? null)
                || $dateIsCurrentMonth($f['vencimiento'] ?? null);
        })->sortBy($receivableMonthRef)->values()->all();
        $duePasados    = $todasEnviadas->filter(function($f) use ($mesIni, $receivableMonthRef) {
            $ref = $receivableMonthRef($f);
            return !$ref || $ref < $mesIni;
        })->sortBy($receivableMonthRef)->values()->all();

        $todasVencidas = $all->filter(fn($f) =>
            ($f['estado'] ?? '') === 'Vencida' ||
            (in_array(($f['estado'] ?? ''), ['Pendiente', 'Enviada'], true) && !empty($f['vencimiento']) && $f['vencimiento'] < $hoy)
        );
        $overdueEsteMes   = $todasVencidas->filter(fn($f) => !empty($f['vencimiento']) && $f['vencimiento'] >= $mesIni && $f['vencimiento'] <= $mesFin)->sortBy('vencimiento')->map($decorate)->values()->all();
        $overdueMesPasado = $todasVencidas->filter(fn($f) => !empty($f['vencimiento']) && $f['vencimiento'] >= $prevIni && $f['vencimiento'] <= $prevFin)->sortBy('vencimiento')->map($decorate)->values()->all();
        $dueEsteMes = collect($dueEsteMes)->map($decorate)->values()->all();
        $duePasados = collect($duePasados)->map($decorate)->values()->all();

        $dueEsteMesTotal    = round(collect($dueEsteMes)->sum($pendingBase), 2);
        $duePasadosTotal    = round(collect($duePasados)->sum($pendingBase), 2);
        $overdueEsteMesTotal   = round(collect($overdueEsteMes)->sum($pendingBase), 2);
        $overdueMesPasadoTotal = round(collect($overdueMesPasado)->sum($pendingBase), 2);
        $dueTotalGlobal = round($todasEnviadas->sum($pendingBase), 2);
        $overdueTotalGlobal = round($todasVencidas->sum($pendingBase), 2);
        $counts = [
            'En borrador'=>$filtered->where('estado','En borrador')->count(),
            'Pendiente'=>$filtered->filter(fn($f) => in_array(($f['estado'] ?? ''), ['Pendiente', 'Enviada'], true))->count(),
            'Pagada'=>$filtered->where('estado','Pagada')->count(),
            'Vencida'=>$filtered->where('estado','Vencida')->count(),
            'Recurrente'=>$filtered->filter(function ($f) {
                $rec = (array) ($f['recurrencia'] ?? []);
                return $this->isConfiguredRecurrence($rec)
                    || !empty($f['recurrencia_origen_id'])
                    || in_array(($f['origen'] ?? ''), ['recurrente', 'recurrente_preemitida'], true);
            })->count(),
        ];
        $recentPayments = $filtered
            ->flatMap(function ($f) {
                return collect($f['pagos'] ?? [])->map(function ($p) use ($f) {
                    return [
                        'factura_id' => $f['id'] ?? null,
                        'numero' => $f['numero'] ?? '',
                        'cliente' => $f['cliente'] ?? '',
                        'monto' => (float) ($p['monto'] ?? 0),
                        'metodo' => $p['metodo'] ?? '',
                        'fecha' => $p['fecha'] ?? '',
                    ];
                });
            })
            ->sortByDesc(fn($p)=> $p['fecha'] ?? '')
            ->take(4)
            ->values()
            ->all();
        $meses_es = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril','May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto','September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];
        $groupByMonth = function ($rows, ?string $dateKey = null) use ($meses_es) {
            return collect($rows)->groupBy(function ($f) use ($dateKey) {
                $source = $dateKey ? ($f[$dateKey] ?? null) : ($f['vencimiento'] ?? $f['fecha'] ?? null);
                if (!$source) return 'Sin fecha';
                return \Illuminate\Support\Carbon::parse($source)->format('Y-m');
            })->map(function ($grupo, $ym) use ($meses_es) {
                if ($ym === 'Sin fecha') {
                    return ['label' => 'Sin fecha', 'facturas' => $grupo->values()->all()];
                }
                $label = \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('F Y');
                foreach ($meses_es as $en => $es) { $label = str_replace($en, $es, $label); }
                return ['label' => $label, 'facturas' => $grupo->values()->all()];
            })->sortKeysDesc()->values()->all();
        };

        $facturasPorMes = collect($facturas)->groupBy(function ($f) {
            return \Illuminate\Support\Carbon::parse($f['fecha'])->format('Y-m');
        })->map(function ($grupo, $ym) use ($meses_es) {
            $label = \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('F Y');
            foreach ($meses_es as $en => $es) { $label = str_replace($en, $es, $label); }
            return ['label' => $label, 'facturas' => $grupo->values()->all()];
        })->sortKeysDesc()->values()->all();
        $dueAll = $todasEnviadas->map($decorate)->values()->all();
        $dueAllByMonth = $groupByMonth($dueAll, 'fecha');
        $overdueAll = $todasVencidas->map($decorate)->values()->all();
        $overdueAllByMonth = $groupByMonth($overdueAll);

        $grandTotal = round(collect($facturas)->sum('_total_base'), 2);

        return view('ventas.facturas_index', compact(
            'facturas','facturasPorMes','grandTotal','total','pagos','due','overdue','q','estado','counts','recentPayments','range','rangeLabel','base',
            'month','navTitle','navMonthLabel','dueEsteMes','duePasados','dueEsteMesTotal','duePasadosTotal',
            'overdueEsteMes','overdueMesPasado','overdueEsteMesTotal','overdueMesPasadoTotal','dueTotalGlobal','overdueTotalGlobal',
            'dueAllByMonth','overdueAllByMonth','paymentMethods'
        ));
    }

    public function pagar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'monto' => 'nullable|numeric',
            'metodo' => 'nullable|string',
            'fecha_pago' => 'nullable|date',
            'nota' => 'nullable|string',
        ]);
        $factura = $this->store->find($data['id']);
        abort_if(!$factura, 404);
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $base = $settings['base_currency'] ?? 'USD';

        $pagos = $factura['pagos'] ?? [];
        $invoiceTotal = (float) ($factura['total'] ?? 0);
        $baseTotal = (float) ($factura['total_base'] ?? $invoiceTotal);
        $isForeign = (($factura['moneda'] ?? $base) !== $base) && $invoiceTotal > 0 && $baseTotal > 0;
        $factor = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;

        $toBase = function ($p) use ($isForeign, $factor, $invoiceTotal) {
            if (isset($p['monto_base'])) {
                return (float) $p['monto_base'];
            }
            $monto = (float) ($p['monto'] ?? 0);
            if (!$isForeign) {
                return $monto;
            }
            // Compatibilidad histórica: montos viejos ambiguos sin monto_base.
            return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
        };

        $abonadoBaseActual = round(collect($pagos)->sum($toBase), 2);
        $pendienteBaseActual = max(0, round($baseTotal - $abonadoBaseActual, 2));

        if (isset($data['monto']) && (float) $data['monto'] > 0) {
            $inputMonto = (float) $data['monto'];
            if ($isForeign && $inputMonto <= ($invoiceTotal * 1.2)) {
                $montoCliente = $inputMonto;
                $montoBase = round($inputMonto * $factor, 2);
            } elseif ($isForeign && $factor > 0) {
                $montoBase = $inputMonto;
                $montoCliente = round($inputMonto / $factor, 2);
            } else {
                $montoCliente = $inputMonto;
                $montoBase = $inputMonto;
            }
        } else {
            $montoBase = $pendienteBaseActual;
            $montoCliente = ($isForeign && $factor > 0)
                ? round($pendienteBaseActual / $factor, 2)
                : $pendienteBaseActual;
        }

        $entry = [
            'fecha' => $data['fecha_pago'] ?? date('Y-m-d'),
            'monto' => $montoCliente,
            'monto_base' => $montoBase,
            'metodo' => $data['metodo'] ?? ($this->paymentMethodOptions()[0] ?? 'Transferencia'),
            'nota' => $data['nota'] ?? '',
        ];
        $pagos[] = $entry;
        $abonadoBase = round(collect($pagos)->sum($toBase), 2);
        $saldoBase = max(0, round($baseTotal - $abonadoBase, 2));
        $saldo = $isForeign && $factor > 0 ? round($saldoBase / $factor, 2) : $saldoBase;
        $estado = $saldoBase <= 0.01 ? 'Pagada' : (($factura['estado'] ?? '')==='Vencida' ? 'Vencida' : 'Pendiente');
        $item = $this->store->update($data['id'], ['pagos'=>$pagos,'saldo'=>$saldo,'saldo_base'=>$saldoBase,'estado'=>$estado]);

        // Si una factura recurrente se paga antes de su emision programada, mover la emision
        // al dia real de pago y recalcular la siguiente recurrencia desde esa nueva base.
        if (
            ($item['estado'] ?? '') === 'Pagada'
            && !empty($item['recurrencia_origen_id'])
            && !empty($factura['fecha'])
            && !empty($entry['fecha'])
            && strtotime((string) $entry['fecha']) < strtotime((string) $factura['fecha'])
        ) {
            $earlyIssueDate = (string) $entry['fecha'];
            $invoicePatch = ['fecha' => $earlyIssueDate];

            if (!empty($factura['vencimiento'])) {
                $creditDays = max(0, (int) floor((strtotime((string) $factura['vencimiento']) - strtotime((string) $factura['fecha'])) / 86400));
                $invoicePatch['vencimiento'] = date('Y-m-d', strtotime($earlyIssueDate . " +{$creditDays} days"));
            }

            $item = $this->store->update((string) $data['id'], $invoicePatch);

            $templateId = (string) ($item['recurrencia_origen_id'] ?? '');
            $template = $templateId !== '' ? $this->store->find($templateId) : null;
            $rec = (array) ($template['recurrencia'] ?? []);
            if ($template && !empty($rec['enabled'])) {
                $everyMonths = max(1, min(12, (int) ($rec['every_months'] ?? 1)));
                $paidAt = \Illuminate\Support\Carbon::parse($earlyIssueDate)->startOfDay();
                $next = $paidAt->copy()->addMonthsNoOverflow($everyMonths)->startOfDay();
                $newDayOfMonth = (int) $paidAt->format('j');
                $next->day(min($newDayOfMonth, $next->daysInMonth));

                $rec['day_of_month'] = $newDayOfMonth;
                $rec['next_send'] = $next->format('Y-m-d');
                $this->store->update($templateId, ['recurrencia' => $rec]);
            }
        }

        if (!empty($item['cliente_id'])) {
            $this->timeline->add($item['cliente_id'], 'factura', [
                'numero'=>$item['numero'] ?? '',
                'total'=>$item['total'] ?? 0,
                'total_base'=>$item['total_base'] ?? null,
                'moneda'=>$item['moneda'] ?? null,
                'estado'=>$item['estado'] ?? '',
                'pago'=>['monto'=>$entry['monto'],'fecha'=>$entry['fecha'],'metodo'=>$entry['metodo']]
            ]);
        }

        $mailSent = false;
        $mailError = null;
        $mailMessage = (($item['estado'] ?? '') === 'Pagada')
            ? 'Pago registrado. Correo de factura pagada enviado.'
            : 'Pago registrado. Correo de factura parcialmente pagada enviado.';

        try {
            if (($item['estado'] ?? '') === 'Pagada') {
                $this->sendInvoicePaidConfirmation($item, $entry);
            } else {
                $this->sendPaymentReceivedNotification($item, $entry, $saldo);
            }
            $mailSent = true;
        } catch (\Throwable $e) {
            $mailError = $e->getMessage();
            $mailMessage = 'Pago registrado, pero no se pudo enviar el correo al cliente.';
        }

        return response()->json([
            'ok' => true,
            'item' => $item,
            'mail_sent' => $mailSent,
            'mail_error' => $mailError,
            'message' => $mailMessage,
        ]);
    }

    public function saveDraft(Request $request, string $id)
    {
        $factura = $this->store->find($id);
        abort_if(!$factura, 404);
        if (($factura['estado'] ?? '') !== 'En borrador') {
            return response()->json(['ok'=>false,'error'=>'not_draft'], 422);
        }
        $data = $request->validate([
            'cliente'               => 'nullable|string|max:255',
            'cliente_id'            => 'nullable|string',
            'proyecto_id'           => 'nullable|string',
            'fecha'                 => 'nullable|date',
            'vencimiento'           => 'nullable|date',
            'moneda'                => 'nullable|string|max:10',
            'tasa'                  => 'nullable|numeric',
            'tax_rate'              => 'nullable|integer|min:0|max:100',
            'items'                 => 'nullable|array',
            'items.*.descripcion'   => 'nullable|string|max:500',
            'items.*.producto_id'   => 'nullable|string',
            'items.*.detalle'       => 'nullable|string|max:5000',
            'items.*.cantidad'      => 'nullable|numeric',
            'items.*.precio'        => 'nullable|numeric',
        ]);
        if (!empty($data['items'])) {
            $subtotal = collect($data['items'])->sum(fn($i) => (float)($i['cantidad'] ?? 0) * (float)($i['precio'] ?? 0));
            $taxRate = (int) round((float) ($data['tax_rate'] ?? $factura['tax_rate'] ?? 0));
            $data['subtotal'] = round($subtotal, 2);
            $data['tax_rate'] = $taxRate;
            $data['impuestos'] = round($subtotal * ($taxRate / 100), 2);
            $data['total'] = round($data['subtotal'] + $data['impuestos'], 2);
        }
        $payload = array_filter($data, fn($v) => $v !== null && $v !== '');
        $this->store->update($id, $payload);
        return response()->json(['ok'=>true,'saved_at'=>now()->format('H:i')]);
    }

    public function tasa(Request $request)
    {
        $data = $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
        ]);
        $from = strtoupper(trim($data['from']));
        $to = strtoupper(trim($data['to']));
        if ($from === $to) {
            return response()->json(['ok'=>true,'rate'=>1]);
        }
        try {
            // Use open.er-api.com (Free, no key required)
            $url = "https://open.er-api.com/v6/latest/{$from}";
            $json = @file_get_contents($url);
            if ($json === false) {
                return response()->json(['ok'=>false,'error'=>'rate_unavailable'], 502);
            }
            $js = json_decode($json, true);
            $rate = $js['rates'][$to] ?? null;
            if (!is_numeric($rate)) {
                return response()->json(['ok'=>false,'error'=>'rate_invalid'], 502);
            }
            return response()->json(['ok'=>true,'rate'=>(float) $rate]);
        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'error'=>'rate_error'], 500);
        }
    }

    protected function resolveRange(string $range): array
    {
        $range = in_array($range, ['month','prev','6m','year']) ? $range : 'month';
        if ($range === 'prev') {
            $start = date('Y-m-01', strtotime('first day of last month'));
            $end = date('Y-m-t', strtotime('last day of last month'));
            return [$start, $end, 'Mes anterior'];
        }
        if ($range === '6m') {
            $start = date('Y-m-01', strtotime('-5 months'));
            $end = date('Y-m-t');
            return [$start, $end, 'Últimos 6 meses'];
        }
        if ($range === 'year') {
            $year = date('Y');
            return ["{$year}-01-01", "{$year}-12-31", 'Año actual'];
        }
        $start = date('Y-m-01');
        $end = date('Y-m-t');
        return [$start, $end, 'Mes actual'];
    }

    protected function formatMonthLabel(string $month): string
    {
        $monthNames = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        ];
        $parts = explode('-', $month);
        if (count($parts) !== 2 || !isset($monthNames[$parts[1]])) {
            return date('F Y');
        }
        return $monthNames[$parts[1]] . ' ' . $parts[0];
    }

    public function duplicar(string $id)
    {
        $orig = $this->store->find($id);
        abort_if(!$orig, 404);
        $nextNumero = $this->nextInvoiceNumber();

        $new = $orig;
        unset($new['id'], $new['created_at'], $new['updated_at']);
        // Copiar contenido, reset de estado y meta
        $new['numero'] = $nextNumero;
        $new['estado'] = 'En borrador';
        $new['fecha'] = date('Y-m-d');
        unset($new['vencimiento'], $new['pagos'], $new['saldo'], $new['sent_at']);

        $item = $this->store->create($new);
        return response()->json([
            'ok'      => true,
            'item'    => $item,
            'edit_url'=> url('/facturas/' . $item['id'] . '/editar'),
        ]);
    }
    public function create()
    {
        $prefill = request('cliente');
        $prefill_id = request('cliente_id');
        $next = $this->nextInvoiceNumber();
        return view('ventas.facturas_create', ['prefill'=>$prefill,'prefill_id'=>$prefill_id,'nextNumber'=>$next]);
    }

    public function store(Request $request)
    {
        $normalizedItems = collect($request->input('items', []))
            ->map(function ($item) {
                return [
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'producto_id' => trim((string) ($item['producto_id'] ?? '')) ?: null,
                    'detalle' => trim((string) ($item['detalle'] ?? '')),
                    'cantidad' => $item['cantidad'] ?? null,
                    'precio' => $item['precio'] ?? null,
                ];
            })
            ->filter(function ($item) {
                $hasDesc = $item['descripcion'] !== '';
                $hasQty = $item['cantidad'] !== null && $item['cantidad'] !== '';
                $hasPrice = $item['precio'] !== null && $item['precio'] !== '';
                return $hasDesc || $hasQty || $hasPrice;
            })
            ->values()
            ->all();
        $request->merge(['items' => $normalizedItems]);

        $data = $request->validate([
            'numero' => 'required|string',
            'cliente' => 'required|string',
            'cliente_id' => 'nullable|string',
            'proyecto_id' => 'nullable|string',
            'fecha' => 'required|date',
            'vencimiento' => 'nullable|date',
            'moneda' => 'nullable|string',
            'tasa' => 'nullable|numeric',
            'tax_rate' => 'nullable|integer|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.producto_id' => 'nullable|string',
            'items.*.detalle' => 'nullable|string|max:5000',
            'items.*.cantidad' => 'nullable|numeric',
            'items.*.precio' => 'nullable|numeric',
            'estado' => 'required|string',
            'recurrence_enabled' => 'nullable|boolean',
            'recurrence_day' => 'nullable|integer|min:1|max:31',
            'recurrence_every_months' => 'nullable|integer|min:1|max:12',
        ]);

        $existingSameNumber = collect($this->store->all())
            ->first(fn($f) => strtoupper(trim((string) ($f['numero'] ?? ''))) === strtoupper(trim((string) $data['numero'])));
        if ($existingSameNumber) {
            return redirect()
                ->route('facturas.show', $existingSameNumber['id'])
                ->with('success', 'La factura ya habia sido creada. Evitamos un duplicado por doble envio.');
        }

        $settings = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
        $defaultTaxRate = (int) round((float) ($settings['tax_rate'] ?? 16));
        $data['tax_rate'] = isset($data['tax_rate']) ? (int) $data['tax_rate'] : $defaultTaxRate;
        $subtotal = collect($data['items'])->sum(fn($i) => (float) ($i['cantidad'] ?? 0) * (float) ($i['precio'] ?? 0));
        $data['subtotal'] = round($subtotal, 2);
        $data['impuestos'] = round($subtotal * ($data['tax_rate'] / 100), 2);
        $data['total'] = round($data['subtotal'] + $data['impuestos'], 2);
        $data['estado'] = $data['estado']==='Borrador' ? 'En borrador' : $data['estado'];
        if ($request->boolean('publicar')) { $data['estado'] = 'Pendiente'; }
        // tasa automática si no se proporcionó y la moneda es distinta a la base
        $base = $settings['base_currency'] ?? 'USD';
        if (!empty($data['moneda']) && $data['moneda'] !== $base && empty($data['tasa'])) {
            try {
                $from = urlencode($data['moneda']);
                $url = "https://open.er-api.com/v6/latest/{$from}";
                $json = @file_get_contents($url);
                if ($json !== false) {
                    $js = json_decode($json, true);
                    $to = $base;
                    if (isset($js['rates'][$to]) && is_numeric($js['rates'][$to])) {
                        $data['tasa'] = (float) $js['rates'][$to];
                    }
                }
            } catch (\Throwable $e) {
                // dejar tasa vacía si falla
            }
        }
        if (!empty($data['moneda']) && $data['moneda'] !== $base && !empty($data['tasa'])) {
            $data['total_base'] = round($data['total'] * (float)$data['tasa'], 2);
        } else {
            $data['total_base'] = null;
        }

        if ($request->boolean('recurrence_enabled')) {
            $day = (int) ($request->input('recurrence_day') ?: date('j'));
            $everyMonths = (int) ($request->input('recurrence_every_months') ?: 1);
            $data['recurrencia'] = [
                'enabled' => true,
                'day_of_month' => max(1, min(31, $day)),
                'every_months' => max(1, min(12, $everyMonths)),
                'next_send' => $this->calculateNextRecurringSendDate($day, $everyMonths, $data['fecha'] ?? date('Y-m-d'), $data['vencimiento'] ?? null, $data['items'] ?? []),
                'lead_days_before' => $this->recurringLeadDaysForItems($data['items'] ?? []),
                'last_sent_at' => null,
            ];
        } else {
            $data['recurrencia'] = null;
        }
        $created = $this->store->create($data);
        $this->addInvoiceTimelineEvent($created, 'Creada');
        $message = 'Factura '.$data['numero'].' creada.';
        if ($request->boolean('publicar')) {
            if ($this->shouldDeferPublishedSend($created)) {
                $this->store->update((string) ($created['id'] ?? ''), [
                    'auto_send_on_issue_date' => true,
                    'auto_send_scheduled_at' => now()->toIso8601String(),
                ]);
                $message .= ' Publicada. Se enviará automáticamente en la fecha de emisión.';
                return redirect()->route('facturas.show', $created['id'])->with('success', $message);
            }
            $autoSend = $this->autoSendPublishedInvoice($created);
            if ($autoSend['sent']) {
                $message .= ' Publicada y enviada al cliente.';
            } else {
                $message .= ' Publicada, pero no se pudo enviar: ' . $autoSend['reason'];
            }
        }
        return redirect()->route('facturas.show', $created['id'])->with('success', $message);
    }

    // generar pública
    public function publico(string $id)
    {
        $invoice = $this->store->find($id);
        abort_if(!$invoice, 404);

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $client = $invoice;

        if (!empty($invoice['cliente_id'])) {
            $clientDb = (new FileStore('clientes.json'))->find($invoice['cliente_id']);
            if ($clientDb) {
                $client = array_merge($client, $clientDb);
            }
        }

        $token = null;
        $useTokenLinks = false;
        $isPublicInvoice = true;
        $publicPayUrl = route('public.pay.checkout', ['invoiceId' => $invoice['id']]);
        $publicPdfUrl = route('facturas.public.pdf', $invoice['id']);

        return view('portal.invoice', compact(
            'client',
            'token',
            'invoice',
            'settings',
            'useTokenLinks',
            'isPublicInvoice',
            'publicPayUrl',
            'publicPdfUrl'
        ));
    }

    // programar recurrencia mensual
    public function programarRecurrencia(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'freq' => 'required|string', // 'mensual'
            'siguiente' => 'required|date',
        ]);
        $item = $this->store->update($data['id'], ['recurrencia'=>['freq'=>$data['freq'], 'siguiente'=>$data['siguiente']]]);
        return response()->json(['ok'=>true,'item'=>$item]);
    }

    public function toggleRecurrencia(Request $request, string $id)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $invoice = $this->store->find($id);
        abort_if(!$invoice, 404);

        $target = $invoice;
        $recurrence = (array) ($target['recurrencia'] ?? []);
        if (!$this->isConfiguredRecurrence($recurrence) && !empty($invoice['recurrencia_origen_id'])) {
            $target = $this->store->find((string) $invoice['recurrencia_origen_id']);
            $recurrence = (array) (($target['recurrencia'] ?? []) ?: []);
        }

        if (!$target || !$this->isConfiguredRecurrence($recurrence)) {
            return response()->json([
                'ok' => false,
                'message' => 'Esta factura no tiene una programación recurrente editable.',
            ], 422);
        }

        $enabled = (bool) $data['enabled'];
        $recurrence['enabled'] = $enabled;
        if ($enabled) {
            $recurrence = $this->moveRecurrenceToNextAvailableCycle($target, $recurrence);
            $recurrence['enabled_at'] = now()->toIso8601String();
            unset($recurrence['disabled_at']);
        } else {
            $recurrence['disabled_at'] = now()->toIso8601String();
        }

        $targetId = (string) ($target['id'] ?? '');
        $this->store->update($targetId, ['recurrencia' => $recurrence]);

        return response()->json([
            'ok' => true,
            'enabled' => $enabled,
            'recurrence_id' => $targetId,
            'next_send' => (string) ($recurrence['next_send'] ?? $recurrence['siguiente'] ?? ''),
            'message' => $enabled
                ? 'Recurrencia activada. La próxima factura seguirá la programación indicada.'
                : 'Recurrencia desactivada. No se generarán ni enviarán nuevas facturas.',
        ]);
    }

    // autoclonar al entrar al índice
    protected function autoClonarRecurrencias()
    {
        $list = $this->store->all();
        $changed = false;
        foreach ($list as $f) {
            $rec = $f['recurrencia'] ?? null;
            $legacyEnabled = !is_array($rec) || !array_key_exists('enabled', $rec) || !empty($rec['enabled']);
            if ($rec && $legacyEnabled && !empty($rec['siguiente']) && strtotime($rec['siguiente']) <= strtotime(date('Y-m-d'))) {
                // clonar
                $new = $f;
                unset($new['id'], $new['recurrencia']);
                $new['estado'] = 'En borrador';
                $new['fecha'] = date('Y-m-d');
                $new['numero'] = $this->nextInvoiceNumber($list);
                // Un clon mensual nunca debe heredar pagos ni saldos del documento original.
                unset($new['pagos'], $new['saldo'], $new['saldo_base'], $new['sent_at']);

                if (!empty($f['vencimiento'])) {
                    $diasCredito = max(0, (int) floor((strtotime($f['vencimiento']) - strtotime($f['fecha'] ?? $f['vencimiento'])) / 86400));
                    $new['vencimiento'] = date('Y-m-d', strtotime($new['fecha'] . " +{$diasCredito} days"));
                }

                $this->store->create($new);
                // siguiente
                $next = date('Y-m-d', strtotime('+1 month', strtotime($rec['siguiente'])));
                $this->store->update($f['id'], ['recurrencia'=>['freq'=>$rec['freq'],'siguiente'=>$next]]);
                $changed = true;
            }
        }
        return $changed;
    }

    protected function nextInvoiceNumber(?array $source = null, string $prefix = 'INV'): string
    {
        $rows = $source ?? $this->store->all();
        $max = 0;

        foreach ($rows as $f) {
            $numero = strtoupper((string) ($f['numero'] ?? ''));
            if ($numero === '') {
                continue;
            }
            // Acepta formatos como INV-0007, INV-0007-DUP o INV-0007-260413.
            if (preg_match('/^' . preg_quote(strtoupper($prefix), '/') . '-(\d+)/', $numero, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return strtoupper($prefix) . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function enviarEmail(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'to' => 'required|email',
            'mail_mode' => 'nullable|string|in:invoice,recurrente,service_due',
            'service_items' => 'nullable|array',
            'service_items.*.title' => 'nullable|string',
            'service_items.*.description' => 'nullable|string',
        ]);
        $factura = $this->store->find($data['id']);
        abort_if(!$factura, 404);
        if (!class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return response()->json(['ok'=>false,'error'=>'dompdf_no_disponible'], 422);
        }

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $invoiceMailer = TemplateMail::configureInvoiceMailer($settings);
        $invoiceFrom = TemplateMail::invoiceFrom($settings);
        $invoiceMeta = [
            'mailer' => $invoiceMailer,
            'from_address' => $invoiceFrom['address'],
            'from_name' => $invoiceFrom['name'],
            'source' => 'factura_email',
            'sent_by' => (string) (auth()->user()->email ?? session('user.email') ?? 'sistema'),
            'sent_by_name' => (string) (auth()->user()->name ?? session('user.name') ?? 'Sistema'),
        ];
        $companyMeta = $this->companyMailMeta('factura_email_empresa_fallback');
        $empresa  = $settings['company_name'] ?? config('app.name');
        $base     = $settings['base_currency'] ?? 'USD';

        $numero   = $factura['numero'] ?? '---';
        $cliente  = $factura['cliente'] ?? 'Cliente';
        $moneda   = $factura['moneda'] ?? null;
        $fecha    = !empty($factura['fecha']) ? \Illuminate\Support\Carbon::parse($factura['fecha'])->format('d/m/Y') : '---';
        $vence    = !empty($factura['vencimiento']) ? \Illuminate\Support\Carbon::parse($factura['vencimiento'])->format('d/m/Y') : null;
        $items    = $factura['items'] ?? [];
        $subtotal = (float)($factura['subtotal'] ?? collect($items)->sum(fn($i) => ((float)($i['cantidad']??0)) * ((float)($i['precio']??0))));
        $taxRate  = (float)($factura['tax_rate'] ?? 0);
        $impuestos = (float) round($subtotal * ($taxRate / 100), 2);
        $total    = (float) round($subtotal + $impuestos, 2);

        // Calcular saldo pendiente
        $pagos        = $factura['pagos'] ?? [];
        $totalPagado  = round(collect($pagos)->sum(fn($p) => (float)($p['monto'] ?? 0)), 2);
        $saldo        = max(0, round($total - $totalPagado, 2));
        $estadoRaw    = $factura['estado'] ?? 'Pendiente';
        $esPagada     = $estadoRaw === 'Pagada' || $saldo <= 0.01;
        $esParcial    = !$esPagada && $totalPagado > 0.01;
        $esVencida    = $estadoRaw === 'Vencida';

        if ($esPagada) {
            $estadoLabel = 'Pagada';
            $estadoColor = '#16a34a'; $estadoBg = '#dcfce7'; $estadoBorder = '#bbf7d0';
        } elseif ($esParcial) {
            $estadoLabel = 'Pago parcial · Debe ' . format_currency($saldo, $moneda);
            $estadoColor = '#b45309'; $estadoBg = '#fef3c7'; $estadoBorder = '#fde68a';
        } elseif ($esVencida) {
            $estadoLabel = 'Vencida · Debe ' . format_currency($saldo, $moneda);
            $estadoColor = '#dc2626'; $estadoBg = '#fee2e2'; $estadoBorder = '#fecaca';
        } else {
            $estadoLabel = 'Pendiente · Debe ' . format_currency($saldo, $moneda);
            $estadoColor = '#2563eb'; $estadoBg = '#eff6ff'; $estadoBorder = '#bfdbfe';
        }

        $subjectTpl = $settings['template_invoice_subject'] ?? 'Factura #{folio} de {empresa}';
        $subject = str_replace(
            ['{cliente}', '{folio}', '{total}', '{vencimiento}', '{empresa}'],
            [$cliente, $numero, format_currency($total, $moneda), $vence ?? '---', $empresa],
            $subjectTpl
        );
        $mailMode = (string) ($data['mail_mode'] ?? 'invoice');

        $linkPublico = route('facturas.public', $factura['id']);
        $linkPdf     = route('facturas.print', $factura['id']);
            $linkPdfPublico = route('facturas.public.pdf', $factura['id']);
        $linkPagar    = route('public.pay.checkout', ['invoiceId' => $factura['id']]);

        // Filas de items
        $rowsHtml = '';
        foreach ($items as $item) {
            $desc  = htmlspecialchars($item['descripcion'] ?? '', ENT_QUOTES);
            $cant  = (float)($item['cantidad'] ?? 0);
            $precio = (float)($item['precio'] ?? 0);
            $rowsHtml .= '<tr style="border-bottom:1px solid #e2e8f0;">'
                . '<td style="padding:8px 0;color:#1e293b;font-size:14px;">' . $desc . '</td>'
                . '<td style="padding:8px 8px;color:#64748b;font-size:14px;text-align:center;">' . number_format($cant, $cant == (int)$cant ? 0 : 2, ',', '.') . '</td>'
                . '<td style="padding:8px 8px;color:#64748b;font-size:14px;text-align:right;">' . format_currency($precio, $moneda) . '</td>'
                . '<td style="padding:8px 0;color:#1e293b;font-size:14px;text-align:right;font-weight:600;">' . format_currency($cant * $precio, $moneda) . '</td>'
                . '</tr>';
        }

        $venceRow = $vence ? '<tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Vencimiento</td><td style="color:#1e293b;font-size:13px;font-weight:600;padding:3px 0;text-align:right;">' . $vence . '</td></tr>' : '';
        $taxRow   = $taxRate > 0 ? '<tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Impuesto (' . (int)$taxRate . '%)</td><td style="color:#1e293b;font-size:13px;padding:3px 0;text-align:right;">' . format_currency($impuestos, $moneda) . '</td></tr>' : '';
        $pagadoRow = $totalPagado > 0.01 ? '<tr><td style="color:#16a34a;font-size:13px;padding:3px 0;">Ya pagado</td><td style="color:#16a34a;font-size:13px;font-weight:600;padding:3px 0;text-align:right;">- ' . format_currency($totalPagado, $moneda) . '</td></tr>' : '';
        $saldoRow  = !$esPagada ? '<tr style="border-top:2px solid #e2e8f0;"><td style="padding-top:8px;font-size:15px;font-weight:700;color:#0f172a;">Saldo pendiente</td><td style="padding-top:8px;font-size:15px;font-weight:700;color:#dc2626;text-align:right;">' . format_currency($saldo, $moneda) . '</td></tr>' : '';

        $botonesHtml = $esPagada
            ? '<a href="' . $linkPublico . '" style="display:inline-block;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:14px;padding:12px 28px;border-radius:50px;text-decoration:none;border:1px solid #e2e8f0;">Ver factura</a>'
                        : '<a href="' . $linkPagar . '" style="display:inline-block;background:#d4f547;color:#0f172a;font-weight:700;font-size:14px;padding:12px 28px;border-radius:50px;text-decoration:none;margin-right:10px;">Pagar ahora</a>'
                            . '<a href="' . $linkPublico . '" style="display:inline-block;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:14px;padding:12px 28px;border-radius:50px;text-decoration:none;border:1px solid #e2e8f0;">Ver factura</a>';

    $btnDescargar = '<a href="' . $linkPdfPublico . '" style="display:inline-block;background:#1e293b;color:#f8fafc;font-weight:700;font-size:14px;padding:12px 28px;border-radius:50px;text-decoration:none;margin-left:8px;">Descargar factura</a>';

    $botonesHtml .= $btnDescargar;

        $serviceInfoHtml = '';
        if ($mailMode === 'service_due') {
            $serviceItems = collect($data['service_items'] ?? [])->filter(fn($s) => !empty($s['title']) || !empty($s['description']))->values();
            if ($serviceItems->isNotEmpty()) {
                $serviceBlocks = $serviceItems->map(function ($s) {
                    $title = htmlspecialchars((string) ($s['title'] ?? ''), ENT_QUOTES);
                    $desc = trim((string) ($s['description'] ?? ''));
                    $descHtml = $desc !== '' ? '<div style="margin-top:6px;white-space:pre-line;color:#334155;font-size:13px;">' . nl2br(htmlspecialchars($desc, ENT_QUOTES)) . '</div>' : '';
                    return '<div style="padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:8px;background:#f8fafc;">'
                        . '<div style="font-size:13px;font-weight:700;color:#0f172a;">' . $title . '</div>'
                        . $descHtml
                        . '</div>';
                })->implode('');
                $serviceInfoHtml = '<div style="margin:0 0 16px;">'
                    . '<div style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Detalle de servicio/producto</div>'
                    . $serviceBlocks
                    . '</div>';
            }
        }

        $openingText = 'Te enviamos la factura <strong>' . htmlspecialchars($numero, ENT_QUOTES) . '</strong> de <strong>' . htmlspecialchars($empresa, ENT_QUOTES) . '</strong>. El PDF va adjunto en este correo.';
        if ($mailMode === 'recurrente') {
            $openingText = 'Se generó tu factura recurrente <strong>' . htmlspecialchars($numero, ENT_QUOTES) . '</strong> de <strong>' . htmlspecialchars($empresa, ENT_QUOTES) . '</strong>. El PDF va adjunto en este correo.';
        } elseif ($mailMode === 'service_due') {
            $openingText = 'Recordatorio de vencimiento: tu factura recurrente <strong>' . htmlspecialchars($numero, ENT_QUOTES) . '</strong> de <strong>' . htmlspecialchars($empresa, ENT_QUOTES) . '</strong> ya está disponible para pago. El PDF va adjunto en este correo.';
        }

        $body = '
<p style="font-size:16px;margin:0 0 12px;">Hola <strong>' . htmlspecialchars($cliente, ENT_QUOTES) . '</strong>,</p>
<p style="font-size:14px;color:#475569;margin:0 0 20px;">' . $openingText . '</p>

<div style="display:inline-block;padding:6px 16px;border-radius:50px;background:' . $estadoBg . ';border:1px solid ' . $estadoBorder . ';color:' . $estadoColor . ';font-size:13px;font-weight:700;margin-bottom:20px;">' . $estadoLabel . '</div>
' . $serviceInfoHtml . '

<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
  <thead>
    <tr style="border-bottom:2px solid #e2e8f0;">
      <th style="text-align:left;padding:8px 0;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Descripción</th>
      <th style="text-align:center;padding:8px 8px;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Cant.</th>
      <th style="text-align:right;padding:8px 8px;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Precio</th>
      <th style="text-align:right;padding:8px 0;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Total</th>
    </tr>
  </thead>
  <tbody>' . $rowsHtml . '</tbody>
</table>

<table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
  <tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Fecha de emisión</td><td style="color:#1e293b;font-size:13px;font-weight:600;padding:3px 0;text-align:right;">' . $fecha . '</td></tr>
  ' . $venceRow . '
  <tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Subtotal</td><td style="color:#1e293b;font-size:13px;padding:3px 0;text-align:right;">' . format_currency($subtotal, $moneda) . '</td></tr>
  ' . $taxRow . '
  <tr style="border-top:2px solid #e2e8f0;">
    <td style="padding-top:8px;font-size:15px;font-weight:700;color:#0f172a;">Total</td>
    <td style="padding-top:8px;font-size:15px;font-weight:700;color:#0f172a;text-align:right;">' . format_currency($total, $moneda) . '</td>
  </tr>
  ' . $pagadoRow . '
  ' . $saldoRow . '
</table>

<div style="text-align:center;margin-bottom:8px;">' . $botonesHtml . '</div>';

        $markInvoiceSent = function (array $extra = []) use ($factura, $data): void {
            if (in_array($factura['estado'] ?? '', ['En borrador', ''])) {
                $this->store->update($factura['id'], ['estado' => 'Pendiente']);
            }
            $this->store->update($factura['id'], array_merge([
                'last_sent_at' => now()->toIso8601String(),
                'last_sent_to' => (string) $data['to'],
            ], $extra));
        };

        try {
            $bin = $this->invoicePdfBinary($factura);
            $invoiceAttachment = [
                'name' => $this->invoicePdfFileName($factura),
                'data' => $bin,
                'mime' => 'application/pdf',
            ];

            TemplateMail::send($data['to'], $subject, $body, $invoiceMeta, $invoiceAttachment);

            $markInvoiceSent(['last_sent_mode' => 'attached_pdf']);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            $attachmentError = trim($e->getMessage());
            $companyAttachmentError = null;

            if (($invoiceMeta['mailer'] ?? '') === 'invoice_smtp' && isset($invoiceAttachment)) {
                try {
                    TemplateMail::send($data['to'], $subject, $body, $companyMeta, $invoiceAttachment);

                    (new FileStore('email_history.json'))->create([
                        'to' => [(string) $data['to']],
                        'subject' => $subject,
                        'body' => 'La factura se envio con el SMTP de Empresa porque fallo el SMTP exclusivo de facturas.',
                        'status' => 'enviado_con_smtp_empresa',
                        'error' => $attachmentError,
                        'sent_by' => (string) ($companyMeta['from_address'] ?? 'sistema'),
                        'sent_by_name' => (string) ($companyMeta['from_name'] ?? 'Sistema'),
                        'source' => 'factura_email_empresa_fallback',
                        'sent_at' => now()->toDateTimeString(),
                    ]);

                    $markInvoiceSent([
                        'last_sent_mode' => 'attached_pdf_company_fallback',
                        'last_sent_invoice_smtp_error' => $attachmentError,
                    ]);

                    return response()->json([
                        'ok' => true,
                        'fallback' => true,
                        'message' => 'Correo enviado con el SMTP principal de Empresa porque falló el SMTP de facturas.',
                    ]);
                } catch (\Throwable $companyException) {
                    $companyAttachmentError = trim($companyException->getMessage());
                }
            }

            return response()->json([
                'ok' => false,
                'error' => $companyAttachmentError
                    ? 'No se pudo enviar la factura con PDF adjunto. SMTP facturas: ' . $attachmentError . ' | SMTP empresa: ' . $companyAttachmentError
                    : 'No se pudo generar o enviar el PDF adjunto: ' . $attachmentError,
                'attachment_error' => $attachmentError,
                'company_attachment_error' => $companyAttachmentError,
            ], 422);
        }
    }

    protected function addInvoiceTimelineEvent(array $invoice, ?string $estado = null): void
    {
        if (empty($invoice['cliente_id'])) {
            return;
        }

        $this->timeline->add((string) $invoice['cliente_id'], 'factura', [
            'numero' => $invoice['numero'] ?? '',
            'total' => $invoice['total'] ?? 0,
            'total_base' => $invoice['total_base'] ?? null,
            'moneda' => $invoice['moneda'] ?? null,
            'estado' => $estado ?? ($invoice['estado'] ?? ''),
            'factura_id' => $invoice['id'] ?? null,
        ]);
    }

    protected function calcTotal($factura) {
        $sub = collect($factura['items'] ?? [])->sum(fn($i)=>($i['cantidad']??0)*($i['precio']??0));
        $taxRate = (float) ($factura['tax_rate'] ?? 0);
        return $sub + ($sub * ($taxRate / 100));
    }

    private function sendPaymentReceivedNotification(array $invoice, array $paymentEntry, float $saldo): void
    {
        $cliente = !empty($invoice['cliente_id']) ? $this->clientes->find((string) $invoice['cliente_id']) : null;
        $to = $cliente['contacto_email'] ?? $cliente['email'] ?? null;
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $linkView = route('facturas.public', (string) $invoice['id']);
        $linkPay = route('public.pay.checkout', ['invoiceId' => $invoice['id']]);

        $settingsTpl = TemplateMail::settings();
        [$subject, $body] = TemplateMail::render(
            $settingsTpl,
            'template_payment_received_subject',
            'template_payment_received_body',
            'Tu factura {folio} se pagó parcialmente',
            "Hola {cliente},\n\nTu factura {folio} se pagó parcialmente. Confirmamos la recepción de tu pago por {monto_pagado}; queda un saldo pendiente de {saldo_restante}.",
            [
                'cliente' => $invoice['cliente'] ?? ($cliente['nombre'] ?? 'Cliente'),
                'folio' => $invoice['numero'] ?? '---',
                'monto_pagado' => (string) ($paymentEntry['monto'] ?? 0),
                'fecha_pago' => (string) ($paymentEntry['fecha'] ?? date('Y-m-d')),
                'metodo_pago' => (string) ($paymentEntry['metodo'] ?? 'Transferencia'),
                'saldo_restante' => (string) $saldo,
                'empresa' => $settingsTpl['company_name'] ?? config('app.name', 'Infocus CRM'),
            ],
            [
                ['label' => 'Ver factura', 'url' => $linkView, 'kind' => 'secondary'],
                ['label' => 'Pagar saldo', 'url' => $linkPay, 'kind' => 'primary'],
            ]
        );

        $this->sendInvoiceNotificationMail((string) $to, $subject, $body, 'factura_payment_received');
    }

    private function sendInvoicePaidConfirmation(array $invoice, array $paymentEntry = []): void
    {
        $cliente = !empty($invoice['cliente_id']) ? $this->clientes->find((string) $invoice['cliente_id']) : null;
        $to = $cliente['contacto_email'] ?? $cliente['email'] ?? null;
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $settingsTpl = TemplateMail::settings();
        $empresa = (string) ($settingsTpl['company_name'] ?? config('app.name', 'Infocus CRM'));
        $numero = (string) ($invoice['numero'] ?? '---');
        $clienteNombre = (string) ($invoice['cliente'] ?? ($cliente['nombre'] ?? 'Cliente'));
        $moneda = $invoice['moneda'] ?? null;
        $fecha = !empty($invoice['fecha']) ? \Illuminate\Support\Carbon::parse($invoice['fecha'])->format('d/m/Y') : '---';
        $vence = !empty($invoice['vencimiento']) ? \Illuminate\Support\Carbon::parse($invoice['vencimiento'])->format('d/m/Y') : null;
        $items = (array) ($invoice['items'] ?? []);
        $subtotal = (float) ($invoice['subtotal'] ?? collect($items)->sum(fn($i) => ((float)($i['cantidad'] ?? 0)) * ((float)($i['precio'] ?? 0))));
        $taxRate = (float) ($invoice['tax_rate'] ?? 0);
        $impuestos = (float) round($subtotal * ($taxRate / 100), 2);
        $total = (float) round($subtotal + $impuestos, 2);
        $totalPagado = (float) round(collect((array) ($invoice['pagos'] ?? []))->sum(fn($p) => (float) ($p['monto'] ?? 0)), 2);
        if ($totalPagado <= 0) {
            $totalPagado = $total;
        }

        $linkView = route('facturas.public', (string) $invoice['id']);
        $linkPdf = route('facturas.public.pdf', (string) $invoice['id']);
        $subject = str_replace(
            ['{folio}', '{cliente}', '{empresa}'],
            [$numero, $clienteNombre, $empresa],
            (string) ($settingsTpl['template_invoice_paid_subject'] ?? '¡Tu factura {folio} ha sido pagada!')
        );

        $rowsHtml = '';
        foreach ($items as $item) {
            $desc = htmlspecialchars((string) ($item['descripcion'] ?? ''), ENT_QUOTES);
            $cant = (float) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);
            $rowsHtml .= '<tr style="border-bottom:1px solid #e2e8f0;">'
                . '<td style="padding:8px 0;color:#1e293b;font-size:14px;">' . $desc . '</td>'
                . '<td style="padding:8px 8px;color:#64748b;font-size:14px;text-align:center;">' . number_format($cant, $cant == (int)$cant ? 0 : 2, ',', '.') . '</td>'
                . '<td style="padding:8px 8px;color:#64748b;font-size:14px;text-align:right;">' . format_currency($precio, $moneda) . '</td>'
                . '<td style="padding:8px 0;color:#1e293b;font-size:14px;text-align:right;font-weight:600;">' . format_currency($cant * $precio, $moneda) . '</td>'
                . '</tr>';
        }

        $venceRow = $vence ? '<tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Vencimiento</td><td style="color:#1e293b;font-size:13px;font-weight:600;padding:3px 0;text-align:right;">' . $vence . '</td></tr>' : '';
        $taxRow = $taxRate > 0 ? '<tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Impuesto (' . (int) $taxRate . '%)</td><td style="color:#1e293b;font-size:13px;padding:3px 0;text-align:right;">' . format_currency($impuestos, $moneda) . '</td></tr>' : '';

        $botonesHtml =
            '<a href="' . $linkView . '" style="display:inline-block;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:14px;padding:12px 28px;border-radius:50px;text-decoration:none;border:1px solid #e2e8f0;">Ver factura</a>'
            . '<a href="' . $linkPdf . '" style="display:inline-block;background:#1e293b;color:#f8fafc;font-weight:700;font-size:14px;padding:12px 28px;border-radius:50px;text-decoration:none;margin-left:8px;">Descargar factura</a>';

        $body = '
<p style="font-size:16px;margin:0 0 12px;">Hola <strong>' . htmlspecialchars($clienteNombre, ENT_QUOTES) . '</strong>,</p>
<p style="font-size:14px;color:#475569;margin:0 0 20px;"><strong>¡Tu factura ' . htmlspecialchars($numero, ENT_QUOTES) . ' ha sido pagada!</strong><br>Te confirmamos que la factura de <strong>' . htmlspecialchars($empresa, ENT_QUOTES) . '</strong> ya se encuentra saldada.</p>

<div style="display:inline-block;padding:6px 16px;border-radius:50px;background:#dcfce7;border:1px solid #bbf7d0;color:#16a34a;font-size:13px;font-weight:700;margin-bottom:20px;">Pagada</div>

<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
  <thead>
    <tr style="border-bottom:2px solid #e2e8f0;">
      <th style="text-align:left;padding:8px 0;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Descripción</th>
      <th style="text-align:center;padding:8px 8px;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Cant.</th>
      <th style="text-align:right;padding:8px 8px;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Precio</th>
      <th style="text-align:right;padding:8px 0;font-size:12px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Total</th>
    </tr>
  </thead>
  <tbody>' . $rowsHtml . '</tbody>
</table>

<table style="width:100%;border-collapse:collapse;margin-bottom:24px;">
  <tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Fecha de emisión</td><td style="color:#1e293b;font-size:13px;font-weight:600;padding:3px 0;text-align:right;">' . $fecha . '</td></tr>
  ' . $venceRow . '
  <tr><td style="color:#64748b;font-size:13px;padding:3px 0;">Subtotal</td><td style="color:#1e293b;font-size:13px;padding:3px 0;text-align:right;">' . format_currency($subtotal, $moneda) . '</td></tr>
  ' . $taxRow . '
  <tr style="border-top:2px solid #e2e8f0;">
    <td style="padding-top:8px;font-size:15px;font-weight:700;color:#0f172a;">Total</td>
    <td style="padding-top:8px;font-size:15px;font-weight:700;color:#0f172a;text-align:right;">' . format_currency($total, $moneda) . '</td>
  </tr>
  <tr style="border-top:2px solid #e2e8f0;">
    <td style="padding-top:8px;font-size:15px;font-weight:700;color:#16a34a;">Pagado</td>
    <td style="padding-top:8px;font-size:15px;font-weight:700;color:#16a34a;text-align:right;">' . format_currency($totalPagado, $moneda) . '</td>
  </tr>
</table>

<div style="text-align:center;margin-bottom:8px;">' . $botonesHtml . '</div>';

        $this->sendInvoiceNotificationMail((string) $to, $subject, $body, 'factura_paid_confirmation');
    }

    private function sendInvoiceNotificationMail(string $to, string $subject, string $body, string $source): void
    {
        try {
            TemplateMail::send($to, $subject, $body, $this->invoiceMailMeta($source));
            return;
        } catch (\Throwable $invoiceError) {
            try {
                TemplateMail::send($to, $subject, $body, $this->companyMailMeta($source . '_empresa_fallback'));
                return;
            } catch (\Throwable) {
                throw $invoiceError;
            }
        }
    }

    private function invoiceMailMeta(string $source): array
    {
        $settings = TemplateMail::settings();
        $from = TemplateMail::invoiceFrom($settings);

        return [
            'mailer' => TemplateMail::configureInvoiceMailer($settings),
            'from_address' => $from['address'],
            'from_name' => $from['name'],
            'source' => $source,
        ];
    }

    private function companyMailMeta(string $source): array
    {
        $settings = TemplateMail::settings();

        return [
            'mailer' => config('mail.default', 'smtp'),
            'from_address' => (string) ($settings['mail_from_address'] ?? config('mail.from.address')),
            'from_name' => (string) ($settings['mail_from_name'] ?? config('mail.from.name')),
            'source' => $source,
            'sent_by' => (string) (auth()->user()->email ?? session('user.email') ?? 'sistema'),
            'sent_by_name' => (string) (auth()->user()->name ?? session('user.name') ?? 'Sistema'),
        ];
    }

    public function cambiarEstado(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'estado' => 'required|string',
        ]);
        $before = $this->store->find($data['id']) ?: [];
        $patch = ['estado' => $data['estado']];
        $paymentEntry = [
            'fecha' => date('Y-m-d'),
            'metodo' => 'Cambio manual de estado',
            'nota' => 'Factura marcada como pagada',
        ];

        if ($data['estado'] === 'Pagada') {
            $settings = (new FileStore('settings.json'))->find('settings') ?: [];
            $base = $settings['base_currency'] ?? 'USD';
            $pagos = (array) ($before['pagos'] ?? []);
            $invoiceTotal = (float) ($before['total'] ?? 0);
            $baseTotal = (float) ($before['total_base'] ?? $invoiceTotal);
            $isForeign = (($before['moneda'] ?? $base) !== $base) && $invoiceTotal > 0 && $baseTotal > 0;
            $factor = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;
            $paidBase = round(collect($pagos)->sum(function ($p) use ($isForeign, $factor, $invoiceTotal) {
                if (isset($p['monto_base'])) {
                    return (float) $p['monto_base'];
                }

                $monto = (float) ($p['monto'] ?? 0);
                if (!$isForeign) {
                    return $monto;
                }

                return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
            }), 2);
            $pendingBase = max(0, round($baseTotal - $paidBase, 2));

            if ($pendingBase > 0.01) {
                $pendingClient = ($isForeign && $factor > 0) ? round($pendingBase / $factor, 2) : $pendingBase;
                $paymentEntry['monto'] = $pendingClient;
                $paymentEntry['monto_base'] = $pendingBase;
                $pagos[] = $paymentEntry;
                $patch['pagos'] = $pagos;
            }

            $patch['saldo'] = 0;
            $patch['saldo_base'] = 0;
        }

        $item = $this->store->update($data['id'], $patch);
        $mailSent = false;
        $mailError = null;
        $message = 'Estado actualizado.';

        if (($before['estado'] ?? '') !== 'Pagada' && ($item['estado'] ?? '') === 'Pagada') {
            try {
                $this->sendInvoicePaidConfirmation($item, $paymentEntry);
                $mailSent = true;
                $message = 'Estado actualizado. Correo de factura pagada enviado.';
            } catch (\Throwable $e) {
                $mailError = $e->getMessage();
                $message = 'Estado actualizado, pero no se pudo enviar el correo al cliente.';
            }
        }

        return response()->json([
            'ok' => true,
            'item' => $item,
            'mail_sent' => $mailSent,
            'mail_error' => $mailError,
            'message' => $message,
        ]);
    }

    public function destroy(string $id)
    {
        $this->store->delete($id);
        $params = request()->only(['q', 'estado', 'range']);
        if (!empty(array_filter($params))) {
            return redirect()->route('facturas.index', $params);
        }
        return redirect()->back();
    }

    public function edit(string $id)
    {
        $factura = $this->store->find($id);
        abort_if(!$factura, 404);
        $recurrenceSource = $factura;
        if (empty(data_get($recurrenceSource, 'recurrencia.enabled')) && !empty($factura['recurrencia_origen_id'])) {
            $template = $this->store->find((string) $factura['recurrencia_origen_id']);
            if ($template && !empty(data_get($template, 'recurrencia.enabled'))) {
                $recurrenceSource = $template;
            }
        }
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $clientes = collect($this->clientes->all())
            ->reject(fn($c) => mb_strtolower(trim((string) ($c['empresa'] ?? ''))) === 'sin cliente')
            ->sortBy(fn($c) => mb_strtolower((string) ($c['empresa'] ?? '')))
            ->values()
            ->all();
        $base = $settings['base_currency'] ?? 'USD';
        return view('ventas.facturas_edit', compact('factura', 'recurrenceSource', 'settings', 'clientes', 'base'));
    }

    public function update(Request $request, string $id)
    {
        $current = $this->store->find($id);
        abort_if(!$current, 404);

        $normalizedItems = collect($request->input('items', []))
            ->map(function ($item) {
                return [
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'producto_id' => trim((string) ($item['producto_id'] ?? '')) ?: null,
                    'detalle' => trim((string) ($item['detalle'] ?? '')),
                    'cantidad' => $item['cantidad'] ?? null,
                    'precio' => $item['precio'] ?? null,
                    'id' => $item['id'] ?? null,
                ];
            })
            ->filter(function ($item) {
                $hasDesc = $item['descripcion'] !== '';
                $hasQty = $item['cantidad'] !== null && $item['cantidad'] !== '';
                $hasPrice = $item['precio'] !== null && $item['precio'] !== '';
                return $hasDesc || $hasQty || $hasPrice;
            })
            ->values()
            ->all();
        $request->merge(['items' => $normalizedItems]);

        $data = $request->validate([
            'cliente' => 'required|string',
            'cliente_id' => 'nullable|string',
            'proyecto_id' => 'nullable|string',
            'fecha' => 'required|date',
            'vencimiento' => 'nullable|date',
            'moneda' => 'nullable|string',
            'tasa' => 'nullable|numeric',
            'tax_rate' => 'nullable|integer|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.producto_id' => 'nullable|string',
            'items.*.detalle' => 'nullable|string|max:5000',
            'items.*.cantidad' => 'nullable|numeric',
            'items.*.precio' => 'nullable|numeric',
            'estado' => 'required|string',
            'recurrence_enabled' => 'nullable|boolean',
            'recurrence_day' => 'nullable|integer|min:1|max:31',
            'recurrence_every_months' => 'nullable|integer|min:1|max:12',
        ]);

        $data['tax_rate'] = isset($data['tax_rate']) ? (int) $data['tax_rate'] : (int) round((float) ($current['tax_rate'] ?? 0));
        $subtotal = collect($data['items'])->sum(fn($i) => (float) $i['cantidad'] * (float) $i['precio']);
        $data['subtotal'] = round($subtotal, 2);
        $data['impuestos'] = round($subtotal * ($data['tax_rate'] / 100), 2);
        $data['total'] = round($data['subtotal'] + $data['impuestos'], 2);
        if ($request->boolean('publicar')) { $data['estado'] = 'Pendiente'; }

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $base = $settings['base_currency'] ?? 'USD';
        if (!empty($data['moneda']) && $data['moneda'] !== $base && empty($data['tasa'])) {
            try {
                $from = urlencode($data['moneda']);
                $url = "https://open.er-api.com/v6/latest/{$from}";
                $json = @file_get_contents($url);
                if ($json !== false) {
                    $js = json_decode($json, true);
                    $to = $base;
                    if (isset($js['rates'][$to]) && is_numeric($js['rates'][$to])) {
                        $data['tasa'] = (float) $js['rates'][$to];
                    }
                }
            } catch (\Throwable $e) {}
        }
        if (!empty($data['moneda']) && $data['moneda'] !== $base && !empty($data['tasa'])) {
            $data['total_base'] = round($data['total'] * (float)$data['tasa'], 2);
        } else {
            $data['total_base'] = null;
        }

        $invoiceTotal = (float) ($data['total'] ?? 0);
        $baseTotal = (float) ($data['total_base'] ?? $invoiceTotal);
        $isForeign = (($data['moneda'] ?? $base) !== $base) && $invoiceTotal > 0 && $baseTotal > 0;
        $factor = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;

        $toBase = function ($p) use ($isForeign, $factor, $invoiceTotal) {
            if (isset($p['monto_base'])) {
                return (float) $p['monto_base'];
            }
            $monto = (float) ($p['monto'] ?? 0);
            if (!$isForeign) {
                return $monto;
            }
            return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
        };

        $pagosExistentes = $current['pagos'] ?? [];
        $abonadoBase = round(collect($pagosExistentes)->sum($toBase), 2);
        $saldoBase = max(0, round($baseTotal - $abonadoBase, 2));
        $saldo = $isForeign && $factor > 0 ? round($saldoBase / $factor, 2) : $saldoBase;

        $data['saldo_base'] = $saldoBase;
        $data['saldo'] = $saldo;

        if (($data['estado'] ?? '') !== 'En borrador') {
            if ($saldoBase <= 0.01) {
                $data['estado'] = 'Pagada';
            } else {
                $data['estado'] = (!empty($data['vencimiento']) && $data['vencimiento'] < date('Y-m-d'))
                    ? 'Vencida'
                    : 'Pendiente';
            }
        }

        $recurrenceTargetId = null;
        $recurrenceTargetData = null;
        $usesOriginRecurrence = empty(data_get($current, 'recurrencia.enabled')) && !empty($current['recurrencia_origen_id']);

        if ($request->boolean('recurrence_enabled')) {
            $recurrenceSource = $current;
            if ($usesOriginRecurrence) {
                $template = $this->store->find((string) $current['recurrencia_origen_id']);
                if ($template && !empty(data_get($template, 'recurrencia.enabled'))) {
                    $recurrenceSource = $template;
                }
            }
            $currentRec = (array) ($recurrenceSource['recurrencia'] ?? []);
            $day = (int) ($request->input('recurrence_day') ?: ($currentRec['day_of_month'] ?? date('j')));
            $everyMonths = (int) ($request->input('recurrence_every_months') ?: ($currentRec['every_months'] ?? 1));

            $recurrenceTargetData = [
                'enabled' => true,
                'day_of_month' => max(1, min(31, $day)),
                'every_months' => max(1, min(12, $everyMonths)),
                'next_send' => $this->calculateNextRecurringSendDate($day, $everyMonths, $data['fecha'] ?? date('Y-m-d'), $data['vencimiento'] ?? null, $data['items'] ?? []),
                'lead_days_before' => $this->recurringLeadDaysForItems($data['items'] ?? []),
                'last_sent_at' => $currentRec['last_sent_at'] ?? null,
                'service_reminder_sent' => $currentRec['service_reminder_sent'] ?? [],
            ];
            if ($usesOriginRecurrence) {
                $recurrenceTargetId = (string) $current['recurrencia_origen_id'];
                $data['recurrencia'] = $current['recurrencia'] ?? null;
            } else {
                $data['recurrencia'] = $recurrenceTargetData;
            }
        } else {
            if ($usesOriginRecurrence) {
                $recurrenceTargetId = (string) $current['recurrencia_origen_id'];
                $recurrenceTargetData = null;
                $data['recurrencia'] = $current['recurrencia'] ?? null;
            } else {
                $data['recurrencia'] = null;
            }
        }

        $data['numero'] = $current['numero'] ?? ($current['id'] ?? $id);
        $updated = $this->store->update($id, $data);
        if ($recurrenceTargetId !== null && $recurrenceTargetId !== (string) ($updated['id'] ?? $id)) {
            $this->store->update($recurrenceTargetId, ['recurrencia' => $recurrenceTargetData]);
        }

        if (($current['estado'] ?? '') !== 'Pagada' && ($updated['estado'] ?? '') === 'Pagada') {
            try {
                $this->sendInvoicePaidConfirmation($updated, [
                    'fecha' => date('Y-m-d'),
                    'metodo' => 'Actualizacion manual de factura',
                ]);
            } catch (\Throwable $e) {
                // Evitar romper el flujo de edicion
            }
        }

        $message = 'Factura actualizada.';
        if ($request->boolean('publicar')) {
            if ($this->shouldDeferPublishedSend($updated)) {
                $this->store->update((string) ($updated['id'] ?? $id), [
                    'auto_send_on_issue_date' => true,
                    'auto_send_scheduled_at' => now()->toIso8601String(),
                ]);
                $message .= ' Publicada. Se enviará automáticamente en la fecha de emisión.';
                return redirect()->route('facturas.show', $updated['id'] ?? $id)->with('success', $message);
            }
            $autoSend = $this->autoSendPublishedInvoice($updated);
            if ($autoSend['sent']) {
                $message .= ' Publicada y enviada al cliente.';
            } else {
                $message .= ' Publicada, pero no se pudo enviar: ' . $autoSend['reason'];
            }
        }

        return redirect()->route('facturas.show', $updated['id'] ?? $id)->with('success', $message);
    }

    protected function calculateNextRecurringDate(int $dayOfMonth, int $everyMonths = 1, ?string $fromDate = null): string
    {
        $day = max(1, min(31, $dayOfMonth));
        $interval = max(1, min(12, $everyMonths));
        $base = $fromDate ? \Illuminate\Support\Carbon::parse($fromDate)->startOfDay() : \Illuminate\Support\Carbon::today();
        $today = \Illuminate\Support\Carbon::today();

        $candidate = $base->copy();
        $candidate->day(min($day, $candidate->daysInMonth));

        if ($candidate->lte($base)) {
            $candidate->addMonthsNoOverflow($interval);
            $candidate->day(min($day, $candidate->daysInMonth));
        }

        // Si la factura base es historica, avanzar hasta la proxima fecha valida (hoy o futura).
        while ($candidate->lt($today)) {
            $candidate->addMonthsNoOverflow($interval);
            $candidate->day(min($day, $candidate->daysInMonth));
        }

        return $candidate->format('Y-m-d');
    }

    private function isConfiguredRecurrence(array $recurrence): bool
    {
        return !empty($recurrence['next_send'])
            || !empty($recurrence['day_of_month'])
            || !empty($recurrence['every_months'])
            || !empty($recurrence['siguiente'])
            || !empty($recurrence['freq']);
    }

    private function moveRecurrenceToNextAvailableCycle(array $invoice, array $recurrence): array
    {
        $isLegacy = empty($recurrence['next_send'])
            && empty($recurrence['day_of_month'])
            && empty($recurrence['every_months'])
            && (!empty($recurrence['siguiente']) || !empty($recurrence['freq']));
        $nextKey = $isLegacy ? 'siguiente' : 'next_send';
        $day = max(1, min(31, (int) ($recurrence['day_of_month'] ?? date('j', strtotime((string) ($recurrence[$nextKey] ?? 'now'))))));
        $everyMonths = max(1, min(12, (int) ($recurrence['every_months'] ?? 1)));
        $leadDays = max(0, (int) ($recurrence['lead_days_before'] ?? 0));

        try {
            $candidate = !empty($recurrence[$nextKey])
                ? \Illuminate\Support\Carbon::parse((string) $recurrence[$nextKey])->startOfDay()
                : null;
        } catch (\Throwable) {
            $candidate = null;
        }

        if (!$candidate) {
            $recurrence[$nextKey] = $this->calculateNextRecurringSendDate(
                $day,
                $everyMonths,
                (string) ($invoice['fecha'] ?? date('Y-m-d')),
                $invoice['vencimiento'] ?? null,
                (array) ($invoice['items'] ?? [])
            );
            return $recurrence;
        }

        $today = \Illuminate\Support\Carbon::today();
        while ($candidate->lt($today)) {
            if ($leadDays > 0) {
                $nextDue = $candidate->copy()->addDays($leadDays)->addMonthsNoOverflow($everyMonths);
                $nextDue->day(min($day, $nextDue->daysInMonth));
                $candidate = $nextDue->subDays($leadDays)->startOfDay();
            } else {
                $candidate->addMonthsNoOverflow($everyMonths);
                $candidate->day(min($day, $candidate->daysInMonth));
            }
        }

        $recurrence[$nextKey] = $candidate->format('Y-m-d');
        return $recurrence;
    }

    protected function calculateNextRecurringSendDate(int $dayOfMonth, int $everyMonths = 1, ?string $issueDate = null, ?string $dueDate = null, array $items = []): string
    {
        $leadDays = $this->recurringLeadDaysForItems($items);
        if ($leadDays <= 0) {
            return $this->calculateNextRecurringDate($dayOfMonth, $everyMonths, $issueDate);
        }

        $day = max(1, min(31, $dayOfMonth));
        $interval = max(1, min(12, $everyMonths));
        $today = \Illuminate\Support\Carbon::today();
        $cycleDue = \Illuminate\Support\Carbon::parse(
            $this->calculateNextRecurringDate($day, $interval, $issueDate)
        )->startOfDay();

        while ($cycleDue->copy()->subDays($leadDays)->lt($today)) {
            $cycleDue->addMonthsNoOverflow($interval);
            $cycleDue->day(min($day, $cycleDue->daysInMonth));
        }

        return $cycleDue->subDays($leadDays)->format('Y-m-d');
    }

    protected function recurringLeadDaysForItems(array $items): int
    {
        $products = collect((new FileStore('productos.json'))->all() ?: []);
        if ($products->isEmpty()) {
            return 0;
        }

        $productsById = $products
            ->filter(fn ($p) => !empty($p['id']))
            ->keyBy(fn ($p) => (string) $p['id']);
        $productsByName = $products
            ->filter(fn ($p) => !empty($p['nombre']))
            ->keyBy(fn ($p) => mb_strtolower(trim((string) $p['nombre'])));

        $leadDays = 0;
        foreach ($items as $item) {
            $product = null;
            $productId = trim((string) ($item['producto_id'] ?? ''));
            if ($productId !== '') {
                $product = $productsById->get($productId);
            }

            if (!$product) {
                $name = mb_strtolower(trim((string) ($item['descripcion'] ?? '')));
                $product = $name !== '' ? $productsByName->get($name) : null;
            }

            if (!$product || empty($product['service_expiry_reminder_enabled'])) {
                continue;
            }

            $leadDays = max($leadDays, max(1, min(90, (int) ($product['service_expiry_reminder_days_before'] ?? 7))));
        }

        return $leadDays;
    }

    public function show(string $id)
    {
        $factura = $this->store->find($id);
        abort_if(!$factura, 404);
        $cliente = $this->clientes->find($factura['cliente_id'] ?? '') ?: [];
        $clienteEmail = $cliente['contacto_email'] ?? '';
        $invoiceFields = $cliente['invoice_fields'] ?? [
            'nit' => true,
            'direccion' => true,
            'telefono' => true,
            'email' => true,
        ];
        $publicUrl = url('/f/'.$id);
        $subject = 'Factura '.$factura['numero'];
        $body = 'Hola, te comparto la factura '.$factura['numero'].': '.$publicUrl;
        $settings = (new \App\Repositories\FileStore('settings.json'))->find('settings') ?: [];
        $whats = $settings['whatsapp_number'] ?? '';
        $waTo = $whats ? 'https://wa.me/'.preg_replace('/\D/','',$whats).'?text='.rawurlencode($subject.' '.$publicUrl) : '#';
        $paymentMethods = $this->paymentMethodOptions();
        $recurrenceSummary = $this->invoiceRecurrenceSummary($factura);
        return view('ventas.facturas_show', compact('factura','cliente','invoiceFields','clienteEmail','publicUrl','subject','body','waTo','paymentMethods','recurrenceSummary'));
    }

    private function invoiceRecurrenceSummary(array $factura): ?array
    {
        $source = $factura;
        if (empty(data_get($source, 'recurrencia.enabled')) && !empty($factura['recurrencia_origen_id'])) {
            $template = $this->store->find((string) $factura['recurrencia_origen_id']);
            if ($template) {
                $source = $template;
            }
        }

        $rec = (array) ($source['recurrencia'] ?? []);
        if (empty($rec['enabled']) && empty($rec['next_send'])) {
            return null;
        }

        $day = max(1, min(31, (int) ($rec['day_of_month'] ?? 1)));
        $everyMonths = max(1, min(12, (int) ($rec['every_months'] ?? 1)));
        $nextSend = (string) ($rec['next_send'] ?? '');

        return [
            'day' => $day,
            'every_months' => $everyMonths,
            'frequency' => $everyMonths === 1 ? 'Cada mes' : "Cada {$everyMonths} meses",
            'next_send' => $nextSend,
            'next_send_human' => $nextSend !== '' ? $this->humanDateEs($nextSend) : 'Pendiente por calcular',
        ];
    }

    private function humanDateEs(string $date): string
    {
        $dt = \Illuminate\Support\Carbon::parse($date);
        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $dt->format('j') . ' de ' . ($months[(int) $dt->format('n')] ?? $dt->format('m')) . ' de ' . $dt->format('Y');
    }

    private function paymentMethodOptions(): array
    {
        $methods = (new FileStore('payment_methods.json'))->all();
        if (isset($methods['id'])) {
            $methods = [$methods];
        }

        $names = collect($methods)
            ->filter(fn($method) => (bool) ($method['active'] ?? true))
            ->map(fn($method) => trim((string) ($method['name'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $names ?: ['Transferencia', 'Efectivo', 'Tarjeta', 'Otro'];
    }

    public function imprimir(string $id)
    {
        $factura = $this->store->find($id);
        abort_if(!$factura, 404);
        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return $this->invoicePdf($factura)->stream($this->invoicePdfFileName($factura));
        }
        return view('ventas.facturas_print', compact('factura'));
    }

    public function descargarPdf(string $id)
    {
        $factura = $this->store->find($id);
        abort_if(!$factura, 404);
        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return $this->invoicePdf($factura)->download($this->invoicePdfFileName($factura));
        }
        return view('ventas.facturas_print', compact('factura'));
    }

    public function publicoPdf(string $id)
    {
        $factura = $this->store->find($id);
        abort_if(!$factura, 404);
        abort_unless(class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf'), 404);
        return $this->invoicePdf($factura)->download($this->invoicePdfFileName($factura));
    }

    private function invoicePdfBinary(array $factura): string
    {
        return $this->invoicePdf($factura)->output();
    }

    private function invoicePdf(array $factura)
    {
        $this->ensureInvoicePdfRuntime();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ventas.facturas_print', [
            'factura' => $factura,
            'pdfMode' => true,
        ])->setPaper('a4');

        if (method_exists($pdf, 'setOptions')) {
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'tempDir' => storage_path('app/dompdf-temp'),
                'chroot' => base_path(),
            ]);
        }

        return $pdf;
    }

    private function ensureInvoicePdfRuntime(): void
    {
        foreach ([storage_path('fonts'), storage_path('app/dompdf-temp')] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    private function invoicePdfFileName(array $factura): string
    {
        $numero = trim((string) ($factura['numero'] ?? $factura['id'] ?? 'factura'));
        $numero = preg_replace('/[^A-Za-z0-9._-]+/', '_', $numero) ?: 'factura';
        return 'factura_' . $numero . '.pdf';
    }

    private function autoSendPublishedInvoice(array $invoice): array
    {
        $cliente = !empty($invoice['cliente_id']) ? $this->clientes->find((string) $invoice['cliente_id']) : null;
        $to = trim((string) ($cliente['contacto_email'] ?? $cliente['email'] ?? ''));

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'reason' => 'el cliente no tiene email valido'];
        }

        try {
            $response = $this->enviarEmail(new Request([
                'id' => (string) ($invoice['id'] ?? ''),
                'to' => $to,
            ]));

            $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
            if (($payload['ok'] ?? false) === true) {
                return ['sent' => true, 'reason' => ''];
            }

            $error = (string) ($payload['error'] ?? 'error desconocido');
            return ['sent' => false, 'reason' => $error];
        } catch (\Throwable $e) {
            return ['sent' => false, 'reason' => $e->getMessage()];
        }
    }

    private function shouldDeferPublishedSend(array $invoice): bool
    {
        $issueDateRaw = (string) ($invoice['fecha'] ?? '');
        if ($issueDateRaw === '') {
            return false;
        }
        try {
            $issueDate = \Illuminate\Support\Carbon::parse($issueDateRaw)->startOfDay();
        } catch (\Throwable $e) {
            return false;
        }

        return $issueDate->gt(\Illuminate\Support\Carbon::today());
    }
}
