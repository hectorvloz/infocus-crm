<?php

namespace App\Mcp\Prompts;

use App\Support\Ai\AiSkills;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('guia_indigo')]
#[Description('Trabaja con el método de Indigo: elige la guía (planear-proyecto, estado-proyecto, brief-creativo, propuesta-comercial, redactar, responder-cliente, leads, contacto-y-reunion, cobro-y-dia) y escribe tu pedido.')]
class GuiaIndigo extends Prompt
{
    public function arguments(): array
    {
        return [
            new Argument('guia', 'Nombre de la guía, ej. planear-proyecto, brief-creativo o leads.', true),
            new Argument('pedido', 'Qué necesitas, ej. "planea la web de Café Laurel".', false),
        ];
    }

    public function handle(Request $request): Response
    {
        $name = trim((string) $request->get('guia'));
        $skills = AiSkills::all();
        $skill = collect($skills)->firstWhere('name', $name) ?? collect(AiSkills::select($name))->first(fn ($s) => !$s['always']);
        $base = collect($skills)->firstWhere('always', true);
        if (!$skill) {
            return Response::text('No existe esa guía. Opciones: ' . collect($skills)->reject(fn ($s) => $s['always'])->pluck('name')->implode(', ') . '.');
        }
        $pedido = trim((string) $request->get('pedido'));

        return Response::text(
            "Usa las herramientas de Infocus y sigue estas guías de trabajo de Indigo.\n\n"
            . ($base ? "# {$base['title']}\n{$base['body']}\n\n" : '')
            . "# {$skill['title']}\n{$skill['body']}"
            . ($pedido !== '' ? "\n\nPedido: {$pedido}" : '')
        );
    }
}
