<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProfileController;
use App\Mcp\Support\McpContext;
use App\Mcp\Support\Reminders;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('completar_recordatorio')]
#[Description('Marca un recordatorio como hecho (o lo reabre con hecho=false). Usa ver_recordatorios para el recordatorio_id.')]
class CompletarRecordatorio extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['recordatorio_id' => $schema->string()->required(), 'hecho' => $schema->boolean()->description('Por defecto true.')];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('panel.read', write: true);
        $d = $request->validate(['recordatorio_id' => 'required|string', 'hecho' => 'nullable|boolean']);
        $done = (bool) ($d['hecho'] ?? true);
        $payload = Reminders::payload();
        $item = collect($payload['items'] ?? [])->firstWhere('id', $d['recordatorio_id']);
        abort_if(!$item, 404);
        $payload['items'] = collect($payload['items'])->map(fn ($i) => ($i['id'] ?? null) === $item['id'] ? ['done' => $done, 'updatedAt' => now()->valueOf()] + $i : $i)->values()->all();

        $this->tracked(Reminders::FILE, Reminders::key(), ($done ? 'Completó' : 'Reabrió') . ' el recordatorio "' . Str::limit((string) $item['text'], 60) . '"',
            fn () => $this->callController(ProfileController::class, 'saveHeaderReminders', ['payload' => $payload]));

        return Response::text('Recordatorio "' . $item['text'] . '" ' . ($done ? 'marcado como hecho.' : 'reabierto.'));
    }
}
