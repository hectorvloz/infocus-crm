<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\PersonalNotes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('editar_nota')]
#[Description('Edita una nota de "Mis notas": cambia el título, agrega contenido al final (modo "agregar", por defecto) o reemplaza todo el contenido (modo "reemplazar"). Antes de reemplazar, lee la nota con ver_nota y confirma con el usuario.')]
class EditarNota extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'nota_id' => $schema->string()->required(),
            'titulo' => $schema->string(),
            'contenido' => $schema->string(),
            'modo' => $schema->string()->enum(['agregar', 'reemplazar'])->description('Por defecto: agregar.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('mis-notas.update', write: true);
        $d = $request->validate([
            'nota_id' => 'required|string', 'titulo' => 'nullable|string|max:500',
            'contenido' => 'nullable|string|max:200000', 'modo' => 'nullable|in:agregar,reemplazar',
        ]);
        if (!isset($d['titulo']) && !isset($d['contenido'])) {
            return Response::error('Indica un título nuevo o contenido.');
        }
        $notes = new PersonalNotes();
        $note = $notes->find($d['nota_id']);
        abort_if(!$note, 404);
        if (!$notes->canEdit($note)) {
            return Response::error('Esta nota te la compartieron solo para ver. No puedes editarla.');
        }

        $html = null;
        if (isset($d['contenido'])) {
            $new = PrepararCorreo::toHtml($d['contenido']);
            $html = ($d['modo'] ?? 'agregar') === 'reemplazar' ? $new : ((string) ($note['html'] ?? '') . $new);
        }
        $notes->update($note, $d['titulo'] ?? null, $html);

        return Response::text('Nota "' . ($d['titulo'] ?? $note['title']) . '" actualizada' . (isset($d['contenido']) ? (($d['modo'] ?? 'agregar') === 'reemplazar' ? ' (contenido reemplazado)' : ' (contenido agregado al final)') : '') . '.');
    }
}
