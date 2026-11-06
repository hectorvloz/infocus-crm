<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\ContractTemplates;
use App\Support\SafeRichText;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('completar_contrato')]
#[Description('Llena los datos entre [CORCHETES] de un contrato que aún no está firmado, ej. {"[PLAZO]": "8 semanas", "[DOMINIO]": "cafelaurel.co"}. Usa solo datos que dio el usuario; no inventes plazos, valores ni fechas. Se puede deshacer con restaurar_cambio.')]
class CompletarContrato extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'contrato_id' => $schema->string()->required(),
            'datos' => $schema->object()->description('Mapa de "[DATO]" => "valor".')->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('contratos.update', write: true);
        $d = $request->validate(['contrato_id' => 'required|string', 'datos' => 'required|array|max:40']);
        $store = $this->store('contratos.json');
        $contract = $store->find($d['contrato_id']);
        abort_if(!$contract, 404);
        if (($contract['estado'] ?? '') === 'Firmado') {
            return Response::error('Ese contrato ya está firmado y no se puede modificar.');
        }

        $html = (string) ($contract['contenido'] ?? '');
        $applied = [];
        foreach ($d['datos'] as $token => $value) {
            $token = '[' . trim((string) $token, " []") . ']';
            $value = trim((string) $value);
            if ($value === '' || !str_contains($html, $token)) {
                continue;
            }
            $html = str_replace($token, e($value), $html);
            $applied[] = $token;
        }
        if (!$applied) {
            return Response::error('No encontré esos datos en el contrato. Pendientes: ' . (implode(', ', ContractTemplates::pending($html)) ?: 'ninguno') . '.');
        }
        $this->tracked('contratos.json', $contract['id'], 'Completó datos del contrato "' . $contract['titulo'] . '"', fn () => $store->update($contract['id'], ['contenido' => SafeRichText::sanitize($html)]));
        $pending = ContractTemplates::pending($html);

        return Response::text('Completé ' . implode(', ', $applied) . '. ' . ($pending ? 'Aún faltan: ' . implode(', ', $pending) . '.' : 'El contrato ya no tiene datos pendientes.'));
    }
}
