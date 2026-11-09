<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('buscar_facturas')]
#[Description('Busca facturas por cliente, número, estado o rango de fechas. Usa ultima_con_pdf para resolver en una llamada la última factura de un cliente y su PDF, incluso con una errata. Para "¿quién me debe?" usa estado Pendiente o Vencida.')]
#[IsReadOnly]
class BuscarFacturas extends InfocusTool
{
    public const ESTADOS = ['En borrador', 'Pendiente', 'Enviada', 'Vencida', 'Pagada'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Número de factura o nombre del cliente.'),
            'cliente_id' => $schema->string()->description('Id del cliente.'),
            'estado' => $schema->string()->enum(self::ESTADOS),
            'desde' => $schema->string()->description('Fecha AAAA-MM-DD.'),
            'hasta' => $schema->string()->description('Fecha AAAA-MM-DD.'),
            'ultima_con_pdf' => $schema->boolean()->description('Devuelve solo la última factura y su PDF. Usa texto o cliente_id para identificar al cliente.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('facturas.read');
        $d = $request->validate([
            'texto' => 'nullable|string|max:120', 'cliente_id' => 'nullable|string', 'estado' => 'nullable|in:' . implode(',', self::ESTADOS),
            'desde' => 'nullable|date', 'hasta' => 'nullable|date',
            'ultima_con_pdf' => 'nullable|boolean',
        ]);
        $invoices = collect($this->store('facturas.json')->all())
            ->when(!empty($d['cliente_id']), fn ($c) => $c->where('cliente_id', $d['cliente_id']));
        $rows = $invoices
            ->when(!empty($d['estado']), fn ($c) => $c->where('estado', $d['estado']))
            ->when(!empty($d['desde']), fn ($c) => $c->filter(fn ($f) => ($f['fecha'] ?? '') >= $d['desde']))
            ->when(!empty($d['hasta']), fn ($c) => $c->filter(fn ($f) => ($f['fecha'] ?? '') <= $d['hasta']));
        $text = (string) ($d['texto'] ?? '');

        if (!empty($d['ultima_con_pdf'])) {
            if (trim($text) === '' && empty($d['cliente_id'])) {
                return Response::error('Indica el cliente o el número de la factura para obtener el PDF.');
            }

            return $this->latestWithPdf($invoices, $rows, trim($text));
        }

        $rows = $rows
            ->filter(fn ($f) => $this->matches(($f['numero'] ?? '') . ' ' . ($f['cliente'] ?? ''), $text))
            ->sortByDesc('fecha')
            ->take(30)
            ->map(fn ($f) => sprintf('- %s · %s · %s · total %s · saldo %s · %s · vence %s (id: %s)',
                $f['numero'] ?? '-', $f['cliente'] ?? '-', $f['fecha'] ?? '-', $this->money($f['total'] ?? 0, $f['moneda'] ?? null),
                $this->money($f['saldo'] ?? $f['total'] ?? 0, $f['moneda'] ?? null), $f['estado'] ?? '-', $f['vencimiento'] ?? '-', $f['id']));

        return Response::text($rows->isEmpty() ? 'No encontré facturas con ese criterio.' : $rows->implode("\n"));
    }

    private function latestWithPdf(Collection $invoices, Collection $period, string $text): Response
    {
        $matches = $invoices->filter(fn ($invoice) => $this->matches(
            ($invoice['numero'] ?? '') . ' ' . ($invoice['cliente'] ?? ''), $text,
        ));

        if ($matches->isEmpty()) {
            $terms = $this->nameWords($text);
            $matches = $invoices->filter(fn ($invoice) => $this->matchesClientName($terms, (string) ($invoice['cliente'] ?? '')));
        }
        if ($matches->isEmpty()) {
            return Response::json(['estado' => 'sin_resultados']);
        }

        $clients = $matches->groupBy(fn ($invoice) => !empty($invoice['cliente_id'])
            ? 'id:' . $invoice['cliente_id']
            : 'nombre:' . implode(' ', $this->nameWords((string) ($invoice['cliente'] ?? ''))));
        if ($clients->count() > 1) {
            return Response::json([
                'estado' => 'cliente_ambiguo',
                'clientes' => $clients->map(fn ($rows) => [
                    'id' => $rows->first()['cliente_id'] ?? null,
                    'nombre' => $rows->first()['cliente'] ?? '',
                ])->values()->all(),
            ]);
        }

        $invoice = $period->intersectByKeys($matches)->sortByDesc(fn ($row) => [
            $row['fecha'] ?? '', $row['created_at'] ?? '', $row['numero'] ?? '',
        ])->first();
        if ($invoice === null) {
            return Response::json(['estado' => 'sin_resultados']);
        }

        return Response::json([
            'estado' => 'encontrada',
            'numero' => $invoice['numero'] ?? $invoice['id'],
            'cliente' => $invoice['cliente'] ?? '',
            'fecha' => $invoice['fecha'] ?? '',
            'pdf_url' => route('facturas.public.pdf', ['id' => $invoice['id']]),
        ]);
    }

    private function nameWords(string $value): array
    {
        return preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($value)), -1, PREG_SPLIT_NO_EMPTY);
    }

    private function matchesClientName(array $terms, string $name): bool
    {
        if ($terms === []) {
            return false;
        }

        $words = $this->nameWords($name);

        return collect($terms)->every(fn ($term) => collect($words)->contains(
            fn ($word) => $word === $term || (
                strlen($term) >= 5 && strlen($word) >= 5
                && ctype_alpha($term) && ctype_alpha($word)
                && levenshtein($term, $word) <= 1
            ),
        ));
    }
}
