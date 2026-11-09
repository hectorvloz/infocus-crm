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
#[Description('Busca facturas por cliente, número, concepto, dominio, estado o fechas. Usa ultima_con_pdf para encontrar la factura y su PDF, incluso con una errata en el cliente o dominio. Para "¿quién me debe?" usa estado Pendiente o Vencida.')]
#[IsReadOnly]
class BuscarFacturas extends InfocusTool
{
    public const ESTADOS = ['En borrador', 'Pendiente', 'Enviada', 'Vencida', 'Pagada'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('Número, nombre del cliente, concepto o dominio que aparece en la factura.'),
            'cliente_id' => $schema->string()->description('Id del cliente.'),
            'estado' => $schema->string()->enum(self::ESTADOS),
            'desde' => $schema->string()->description('Fecha AAAA-MM-DD.'),
            'hasta' => $schema->string()->description('Fecha AAAA-MM-DD.'),
            'ultima_con_pdf' => $schema->boolean()->description('Devuelve solo la última factura y su PDF. Usa texto para buscar por cliente, concepto o dominio, o cliente_id si ya lo tienes.'),
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
                return Response::error('Indica el cliente, número, concepto o dominio para obtener el PDF.');
            }

            return $this->latestWithPdf($invoices, $rows, trim($text));
        }

        $rows = $rows
            ->filter(fn ($f) => $this->matches(($f['numero'] ?? '') . ' ' . ($f['cliente'] ?? ''), $text)
                || $this->conceptDistance($f, $text) === 0)
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
        $matchType = null;
        if ($matches->isEmpty()) {
            $scores = $period->map(fn ($invoice) => $this->conceptDistance($invoice, $text))
                ->filter(fn ($score) => $score !== null);
            if ($scores->isEmpty()) {
                return Response::json(['estado' => 'sin_resultados']);
            }
            $best = $scores->min();
            $matches = $period->intersectByKeys($scores->filter(fn ($score) => $score === $best));
            $matchType = $best === 0 ? 'concepto' : 'dominio_similar';
        }

        $clients = $matches->groupBy(fn ($invoice) => !empty($invoice['cliente_id'])
            ? 'id:' . $invoice['cliente_id']
            : 'nombre:' . implode(' ', $this->nameWords((string) ($invoice['cliente'] ?? ''))));
        $ordered = $period->intersectByKeys($matches)->sortByDesc(fn ($row) => [
            $row['fecha'] ?? '', $row['created_at'] ?? '', $row['numero'] ?? '',
        ]);
        if ($matchType !== null && ($clients->count() > 1 || ($matchType === 'dominio_similar' && $ordered->count() > 1))) {
            return Response::json([
                'estado' => 'factura_ambigua',
                'facturas' => $ordered->take(5)->map(fn ($row) => [
                    'numero' => $row['numero'] ?? $row['id'],
                    'cliente' => $row['cliente'] ?? '',
                    'fecha' => $row['fecha'] ?? '',
                ])->values()->all(),
            ]);
        }
        if ($clients->count() > 1) {
            return Response::json([
                'estado' => 'cliente_ambiguo',
                'clientes' => $clients->map(fn ($rows) => [
                    'id' => $rows->first()['cliente_id'] ?? null,
                    'nombre' => $rows->first()['cliente'] ?? '',
                ])->values()->all(),
            ]);
        }

        $invoice = $ordered->first();
        if ($invoice === null) {
            return Response::json(['estado' => 'sin_resultados']);
        }

        return Response::json([
            'estado' => 'encontrada',
            'numero' => $invoice['numero'] ?? $invoice['id'],
            'cliente' => $invoice['cliente'] ?? '',
            'fecha' => $invoice['fecha'] ?? '',
            'pdf_url' => route('facturas.public.pdf', ['id' => $invoice['id']]),
        ] + ($matchType === null ? [] : ['coincidencia' => $matchType]));
    }

    private function conceptDistance(array $invoice, string $text): ?int
    {
        $query = Str::lower(Str::ascii(trim($text)));
        if ($query === '') {
            return null;
        }

        $best = null;
        foreach ($invoice['items'] ?? [] as $item) {
            foreach (['descripcion', 'detalle'] as $field) {
                $concept = (string) ($item[$field] ?? '');
                if ($this->matches($concept, $text)) {
                    return 0;
                }

                preg_match_all('/\b([a-z0-9-]+)\.(?:com|co|net|org|io|edu|gov|info|biz|online|shop|store|site|app|dev|me|es)(?=\b)/i', $concept, $domains);
                foreach ($domains[1] as $label) {
                    $label = strtolower($label);
                    if ($label === $query) {
                        return 0;
                    }

                    // A longer brand can omit a short segment, but must keep distinctive edges.
                    if (strlen($query) < 8 || !ctype_alnum($query)
                        || substr($label, 0, 2) !== substr($query, 0, 2)
                        || substr($label, -4) !== substr($query, -4)) {
                        continue;
                    }
                    $distance = levenshtein($query, $label);
                    if ($distance <= min(3, intdiv(strlen($query), 4))) {
                        $best = $best === null ? $distance : min($best, $distance);
                    }
                }
            }
        }

        return $best;
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
