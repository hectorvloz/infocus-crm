<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\Ai\AiSkills;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_guia')]
#[Description('Devuelve la guía de trabajo de Indigo para un tipo de tarea: planear-proyecto, estado-proyecto, brief-creativo, propuesta-comercial, redactar, responder-cliente, leads, contacto-y-reunion, cobro-y-dia o entender-pedidos. Consúltala ANTES de planear proyectos, escribir briefs, propuestas, mensajes o respuestas a clientes, trabajar con leads, cobros o el resumen del día, y síguela.')]
#[IsReadOnly]
class VerGuia extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'tema' => $schema->string()->description('Nombre de la guía, o una descripción del pedido para elegir la adecuada.')->required(),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $tema = trim((string) $request->validate(['tema' => 'required|string|max:300'])['tema']);
        $skills = AiSkills::all();
        $exact = collect($skills)->firstWhere('name', $tema);
        $chosen = $exact ? [$exact] : array_values(array_filter(AiSkills::select($tema), fn ($s) => !$s['always']));
        if (!$chosen) {
            $list = collect($skills)->map(fn ($s) => "- {$s['name']}: {$s['title']}")->implode("\n");

            return Response::text("No encontré una guía para «{$tema}». Guías disponibles:\n{$list}");
        }

        return Response::text(implode("\n\n", array_map(fn ($s) => "# {$s['title']} ({$s['name']})\n\n{$s['body']}", $chosen)));
    }
}
