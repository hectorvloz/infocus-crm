<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProductosController;
use App\Mcp\Support\McpContext;
use App\Mcp\Support\McpDenied;
use App\Mcp\Support\McpHistory;
use App\Mcp\Support\PersonalNotes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('restaurar_cambio')]
#[Description('Deja una nota, proyecto, lead, producto, servicio o tus recordatorios exactamente como estaban antes de una edición del MCP. Usa ver_cambios_recientes para el cambio_id. Si alguien lo editó después, la herramienta avisa: solo usa forzar=true cuando el usuario acepte perder esos cambios posteriores. La restauración también se puede deshacer.')]
class RestaurarCambio extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'cambio_id' => $schema->string()->required(),
            'forzar' => $schema->boolean()->description('Restaurar aunque haya cambios posteriores (se pierden).'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $d = $request->validate(['cambio_id' => 'required|string', 'forzar' => 'nullable|boolean']);
        $history = new McpHistory();
        $entry = $history->find($d['cambio_id']);
        if (!$entry) {
            return Response::error('No encontré ese cambio (solo se guardan 30 días y solo los tuyos).');
        }
        [$type, $permission] = McpHistory::FILES[$entry['file']] ?? [null, null];
        if (!$type) {
            return Response::error('Ese tipo de cambio no se puede restaurar.');
        }
        if ($permission) {
            $ctx->allow($permission, write: true);
        } elseif (!$ctx->canWrite()) {
            throw new McpDenied('Esta conexión es de solo lectura. Crea una conexión con acceso completo en Configuración → MCP.');
        }
        if ($entry['file'] === 'mis_notas.json') {
            $notes = new PersonalNotes();
            $note = $notes->find($entry['record_id']);
            if (!$note || !$notes->canEdit($note)) {
                return Response::error('Ya no tienes permiso para editar esa nota.');
            }
        }
        $restore = function () use ($history, $entry, $d, $type, $ctx) {
            if ($history->changedSince($entry) && empty($d['forzar'])) {
                return Response::error("Después de ese cambio alguien volvió a editar este {$type}. Si restauro, también se pierden esas ediciones posteriores. Pregúntale al usuario y, si acepta, llama de nuevo con forzar=true.");
            }

            $history->restore($entry, $ctx->connection);

            return Response::text("Listo: restauré el {$type} a como estaba antes de «{$entry['label']}». Si fue un error, aparece en ver_cambios_recientes y también se puede restaurar.");
        };

        return $entry['file'] === 'productos.json' ? ProductosController::writeLocked($restore) : $restore();
    }
}
