<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PersonalNotes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('crear_nota')]
#[Description('Crea una nota en "Mis notas". El contenido puede ser texto (párrafos separados por línea en blanco) o HTML simple (h2, p, strong, em, ul, li).')]
class CrearNota extends InfocusTool
{
    public const COLORS = ['yellow', 'blue', 'green', 'pink', 'purple', 'gray'];

    public function schema(JsonSchema $schema): array
    {
        return [
            'titulo' => $schema->string()->required(),
            'contenido' => $schema->string()->required(),
            'cliente_id' => $schema->string()->description('Opcional: vincula la nota a un cliente.'),
            'color' => $schema->string()->enum(self::COLORS),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('mis-notas.create', write: true);
        $d = $request->validate([
            'titulo' => 'required|string|max:500', 'contenido' => 'required|string|max:200000',
            'cliente_id' => 'nullable|string', 'color' => 'nullable|in:' . implode(',', self::COLORS),
        ]);
        $client = null;
        if (!empty($d['cliente_id'])) {
            $client = $this->store('clientes.json')->find($d['cliente_id']);
            abort_if(!$client, 404);
        }
        $note = (new PersonalNotes())->create($d['titulo'], PrepararCorreo::toHtml($d['contenido']), $d['color'] ?? 'yellow', $client);

        return Response::text("Nota creada: \"{$note['title']}\" (nota_id: {$note['id']})" . ($client ? " · vinculada a {$client['empresa']}" : '') . '.');
    }
}
