<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PersonalNotes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_notas')]
#[Description('Lista tus notas de "Mis notas" (propias y compartidas contigo), opcionalmente buscando por texto o cliente.')]
#[IsReadOnly]
class ListarNotas extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['texto' => $schema->string()->description('Busca en título, contenido o cliente.')];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('mis-notas.read');
        $q = (string) ($request->validate(['texto' => 'nullable|string|max:120'])['texto'] ?? '');
        $notes = new PersonalNotes();
        $rows = collect($notes->visible())
            ->filter(fn ($n) => $this->matches(($n['title'] ?? '') . ' ' . ($n['plainText'] ?? '') . ' ' . ($n['linkedClient'] ?? ''), $q))
            ->take(30)
            ->map(fn ($n) => sprintf('- %s (nota_id: %s) · %s · actualizada %s%s · %s',
                $n['title'] ?? 'Sin título', $n['id'], $notes->isOwner($n) ? 'tuya' : 'compartida por ' . ($n['ownerName'] ?? 'otro usuario'),
                !empty($n['updatedAt']) ? Carbon::createFromTimestampMs((int) $n['updatedAt'])->setTimezone(config('app.timezone'))->format('Y-m-d') : '-',
                !empty($n['linkedClient']) ? " · cliente: {$n['linkedClient']}" : '',
                Str::limit(preg_replace('/\s+/', ' ', (string) ($n['plainText'] ?? '')), 90)));

        return Response::text($rows->isEmpty() ? 'No encontré notas.' : $rows->implode("\n"));
    }
}
