<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('resumen_negocio')]
#[Description('Resumen de un mes: facturado, cobrado, por cobrar, vencido, gastos y leads nuevos. Úsalo para preguntas como "¿cómo vamos este mes?".')]
#[IsReadOnly]
class ResumenNegocio extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'mes' => $schema->string()->description('Mes en formato AAAA-MM. Por defecto, el mes actual.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read');
        $data = $request->validate(['mes' => 'nullable|date_format:Y-m']);
        $start = Carbon::createFromFormat('Y-m', $data['mes'] ?? now()->format('Y-m'))->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $inMonth = fn (?string $date) => $date && Carbon::parse($date)->between($start, $end);

        $facturas = collect($this->store('facturas.json')->all())->reject(fn ($f) => in_array($f['estado'] ?? '', ['En borrador', 'Borrador'], true));
        $facturado = $facturas->filter(fn ($f) => $inMonth($f['fecha'] ?? null))->sum(fn ($f) => (float) ($f['total_base'] ?? $f['total'] ?? 0));
        $cobrado = $facturas->flatMap(fn ($f) => $f['pagos'] ?? [])->filter(fn ($p) => $inMonth($p['fecha'] ?? null))->sum(fn ($p) => (float) ($p['monto_base'] ?? $p['monto'] ?? 0));
        $pendientes = $facturas->filter(fn ($f) => in_array($f['estado'] ?? '', ['Pendiente', 'Enviada', 'Vencida'], true));
        $porCobrar = $pendientes->sum(fn ($f) => (float) ($f['saldo_base'] ?? $f['saldo'] ?? $f['total_base'] ?? $f['total'] ?? 0));
        $vencidas = $pendientes->filter(fn ($f) => ($f['estado'] ?? '') === 'Vencida' || (!empty($f['vencimiento']) && Carbon::parse($f['vencimiento'])->isPast()));

        $lineas = [
            'Mes: ' . $start->translatedFormat('F Y'),
            'Facturado: ' . $this->money($facturado, null),
            'Cobrado: ' . $this->money($cobrado, null),
            'Por cobrar (todas las pendientes): ' . $this->money($porCobrar, null) . ' en ' . $pendientes->count() . ' facturas',
            'Vencidas: ' . $vencidas->count(),
        ];
        if (\App\Support\RoleAccess::can(auth()->user(), 'gastos.read')) {
            $gastos = collect($this->store('gastos.json')->all())->filter(fn ($g) => $inMonth($g['fecha'] ?? null))->sum(fn ($g) => (float) ($g['monto'] ?? 0));
            $lineas[] = 'Gastos del mes: ' . $this->money($gastos, null);
        }
        if (\App\Support\RoleAccess::can(auth()->user(), 'leads.read')) {
            $leads = collect($this->store('leads.json')->all())->filter(fn ($l) => $inMonth($l['created_at'] ?? null))->count();
            $lineas[] = 'Leads nuevos: ' . $leads;
        }
        $lineas[] = 'Montos en la moneda base configurada en Infocus.';

        return Response::text(implode("\n", $lineas));
    }
}
