<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;

class PagosController extends Controller
{
    protected FileStore $facturas;
    protected FileStore $clientes;

    public function __construct()
    {
        $this->facturas = new FileStore('facturas.json');
        $this->clientes = new FileStore('clientes.json');
    }

    public function index(Request $request)
    {
        $clienteId = (string) $request->query('cliente_id', '');
        $desde = (string) $request->query('desde', '');
        $hasta = (string) $request->query('hasta', '');

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = $settings['base_currency'] ?? 'USD';

        $clientes = collect($this->clientes->all())
            ->map(fn($c) => ['id'=>$c['id'] ?? '', 'empresa'=>$c['empresa'] ?? ''])
            ->sortBy('empresa')
            ->values()
            ->all();

        $pagos = collect($this->facturas->all())
            ->flatMap(function ($f) use ($baseCurrency) {
                $moneda       = $f['moneda'] ?? $baseCurrency;
                $invoiceTotal = (float) ($f['total'] ?? 0);
                $baseTotal    = (float) ($f['total_base'] ?? $invoiceTotal);
                $isForeign    = $moneda !== $baseCurrency && $invoiceTotal > 0 && $baseTotal > 0;
                $factor       = $isForeign ? ($baseTotal / $invoiceTotal) : 1.0;

                // Misma lógica que FacturasController::$effPaid
                $toBase = function ($p) use ($isForeign, $factor, $invoiceTotal) {
                    if (isset($p['monto_base'])) {
                        return (float) $p['monto_base'];
                    }
                    $monto = (float) ($p['monto'] ?? 0);
                    if (!$isForeign) {
                        return $monto;
                    }
                    // Si monto <= 120% del total en moneda cliente → es moneda cliente, convertir
                    // Si es mayor → ya está en moneda base
                    return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
                };

                $factSaldoBase = (float) ($f['saldo_base'] ?? 0);
                $totalPagosFactura = round(collect($f['pagos'] ?? [])->sum(function ($p) use ($toBase) {
                    return $toBase($p);
                }), 2);
                // Para determinar si un pago es abono: acumular y ver si tras él la factura queda saldada
                $acumulado = 0;
                $pagosSaldados = [];
                foreach ($f['pagos'] ?? [] as $p) {
                    $acumulado = round($acumulado + $toBase($p), 2);
                    $pagosSaldados[] = $acumulado >= ($baseTotal - 0.5); // queda saldada tras este pago
                }

                return collect($f['pagos'] ?? [])->values()->map(function ($p, $idx) use ($f, $moneda, $isForeign, $toBase, $factor, $invoiceTotal, $baseCurrency, $pagosSaldados) {
                    $montoRaw   = (float) ($p['monto'] ?? 0);
                    $montoBase  = round($toBase($p), 2);

                    // Monto a mostrar en moneda del cliente
                    if (!$isForeign) {
                        $montoForeign = $montoBase;
                    } elseif (isset($p['monto_base'])) {
                        $montoForeign = $montoRaw;
                    } elseif ($montoRaw <= ($invoiceTotal * 1.2)) {
                        $montoForeign = $montoRaw;
                    } else {
                        $montoForeign = $factor > 0 ? round($montoBase / $factor, 2) : $montoRaw;
                    }

                    // Es abono si tras este pago la factura NO queda saldada
                    $saldaTras = $pagosSaldados[$idx] ?? false;

                    return [
                        'factura_id'    => $f['id'] ?? '',
                        'pago_index'    => $idx,
                        'numero'        => $f['numero'] ?? '',
                        'cliente_id'    => $f['cliente_id'] ?? '',
                        'cliente'       => $f['cliente'] ?? '',
                        'monto'         => $montoForeign,
                        'monto_base'    => $montoBase,
                        'moneda'        => $moneda,
                        'es_extranjera' => $isForeign,
                        'es_abono'      => !$saldaTras,
                        'metodo'        => $p['metodo'] ?? '',
                        'fecha'         => $p['fecha'] ?? '',
                        'nota'          => $p['nota'] ?? '',
                    ];
                });
            })
            ->when($clienteId !== '', fn($c) => $c->where('cliente_id', $clienteId))
            ->when($desde !== '', fn($c) => $c->filter(fn($p) => !empty($p['fecha']) && $p['fecha'] >= $desde))
            ->when($hasta !== '', fn($c) => $c->filter(fn($p) => !empty($p['fecha']) && $p['fecha'] <= $hasta))
            ->sortByDesc('fecha')
            ->values();

        // Total en moneda base
        $totalPagos = round($pagos->sum('monto_base'), 2);
        $countPagos = $pagos->count();

        // Paginación manual de 10 por página
        $perPage  = 10;
        $page     = max(1, (int) $request->query('page', 1));
        $total    = $pagos->count();
        $pagos    = $pagos->forPage($page, $perPage)->values()->all();
        $lastPage = (int) ceil($total / $perPage) ?: 1;

        return view('ventas.pagos', compact('pagos', 'clientes', 'clienteId', 'desde', 'hasta', 'totalPagos', 'countPagos', 'baseCurrency', 'page', 'lastPage', 'perPage'));
    }

    public function destroy(Request $request, string $facturaId, int $pagoIndex)
    {
        $factura = $this->facturas->find($facturaId);
        abort_if(!$factura, 404);

        $pagos = $factura['pagos'] ?? [];
        if (!array_key_exists($pagoIndex, $pagos)) {
            return redirect()->back();
        }

        array_splice($pagos, $pagoIndex, 1);

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $base = $settings['base_currency'] ?? 'USD';

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
            return $monto <= ($invoiceTotal * 1.2) ? ($monto * $factor) : $monto;
        };

        $abonadoBase = round(collect($pagos)->sum($toBase), 2);
        $saldoBase = max(0, round($baseTotal - $abonadoBase, 2));
        $saldo = $isForeign && $factor > 0 ? round($saldoBase / $factor, 2) : $saldoBase;

        $estadoActual = $factura['estado'] ?? 'Pendiente';
        if ($estadoActual === 'En borrador') {
            $estado = 'En borrador';
        } elseif ($saldoBase <= 0.01) {
            $estado = 'Pagada';
        } elseif (!empty($factura['vencimiento']) && $factura['vencimiento'] < date('Y-m-d')) {
            $estado = 'Vencida';
        } else {
            $estado = 'Pendiente';
        }

        $this->facturas->update($facturaId, [
            'pagos' => $pagos,
            'saldo' => $saldo,
            'saldo_base' => $saldoBase,
            'estado' => $estado,
        ]);

        return redirect()->back();
    }
}
