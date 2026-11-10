<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\ProyectosController;
use App\Mcp\Support\McpContext;
use App\Support\Ai\AiAttachments;
use App\Support\DocumentStorage;
use App\Support\ProjectCovers;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

#[Name('gestionar_portada_proyecto')]
#[Description('Cambia la portada de un proyecto. Usa listar_portadas_proyecto para consultar opciones e imágenes existentes. Para una imagen nueva usa subir_adjunto y luego su ID ADJ. La acción predeterminada restablece la apariencia del proyecto. Se puede deshacer con restaurar_cambio.')]
class GestionarPortadaProyecto extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'proyecto_id' => $schema->string()->required(),
            'accion' => $schema->string()->enum(['galeria', 'color', 'archivo', 'adjunto', 'predeterminada'])->required(),
            'valor' => $schema->string()->description('Nombre de galería, color #RRGGBB o #RRGGBB|#RRGGBB, ID de archivo del proyecto o ID ADJ del adjunto. Omite para predeterminada.'),
        ];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.update', write: true);
        $d = $request->validate([
            'proyecto_id' => 'required|string|max:100',
            'accion' => 'required|in:galeria,color,archivo,adjunto,predeterminada',
            'valor' => 'required_unless:accion,predeterminada|nullable|string|max:160',
        ]);
        $value = trim((string) ($d['valor'] ?? ''));

        return Cache::lock('mcp-project-covers', 60)->block(5, function () use ($d, $value) {
            $project = $this->store('proyectos.json')->find($d['proyecto_id']);
            abort_if(!$project, 404);
            $id = $project['id'];
            $selection = match ($d['accion']) {
                'galeria' => $this->galleryCover($value),
                'color' => $this->colorCover($value),
                'archivo' => $this->fileCover($project, $value),
                'adjunto' => $this->attachmentCover($project, $value),
                'predeterminada' => ['cover_image' => null, 'cover_color' => null],
            };
            if ($selection instanceof Response) {
                return $selection;
            }
            if (is_array($selection) && collect($selection)->every(fn ($value, $key) => ($project[$key] ?? null) === $value)) {
                return Response::text('El proyecto ya tiene esa portada.');
            }

            $label = 'Cambió la portada del proyecto ' . $this->external($project['titulo'] ?? '');
            $result = $this->tracked('proyectos.json', $id, $label, function () use ($id, $selection, $value) {
                if ($selection instanceof UploadedFile) {
                    $request = HttpRequest::create('/', 'POST', ['id' => $id], files: ['file' => $selection]);

                    return app(ProyectosController::class)->uploadArchivo($request, strtoupper($value))->getData(true);
                }

                return $this->callController(ProyectosController::class, 'actualizar', ['id' => $id] + $selection)['data'];
            });
            if (empty($result['item'])) {
                return Response::error('No se pudo guardar la portada.');
            }

            return Response::text('Portada actualizada para ' . $this->external($project['titulo'] ?? '') . '.');
        });
    }

    private function galleryCover(string $value): array|Response
    {
        $name = Str::lower(Str::ascii($value));
        $preset = collect(ProjectCovers::PRESETS)->first(fn ($item) => Str::lower(Str::ascii($item['name'])) === $name);

        return $preset
            ? ['cover_image' => $preset['url']]
            : Response::error('No encontré esa portada. Usa listar_portadas_proyecto para ver los nombres.');
    }

    private function colorCover(string $value): array|Response
    {
        if (!preg_match('/^#[0-9a-f]{6}(?:\s*\|\s*#[0-9a-f]{6})?$/iD', $value)) {
            return Response::error('Usa un color #RRGGBB o dos separados por |.');
        }

        [$start, $end] = array_pad(array_map('trim', explode('|', strtolower($value))), 2, null);

        return ['cover_image' => null, 'cover_color' => $start . '|' . ($end ?? $start)];
    }

    private function fileCover(array $project, string $id): array|Response
    {
        $image = collect(ProjectCovers::imageFiles($project))->firstWhere('id', $id);
        $document = $image ? $this->store('documentos.json')->find($id) : null;
        $path = (string) ($document['path'] ?? '');
        if (!$document || ($document['storage'] ?? 'local') !== 'local' || !isset(ProjectCovers::IMAGE_EXTENSIONS[$document['mime'] ?? ''])
            || $path === '' || !DocumentStorage::disk($path, false)) {
            return Response::error('No encontré una imagen disponible con ese ID en este proyecto.');
        }

        return ['cover_image' => ProjectCovers::documentPreview($id)];
    }

    private function attachmentCover(array $project, string $id): array|UploadedFile|Response
    {
        $attachment = (new AiAttachments())->find($id);
        if (!$attachment || !AiAttachments::isImage($attachment)) {
            return Response::error('No encontré un adjunto de imagen vigente en tu cuenta.');
        }

        $existing = collect(ProjectCovers::imageFiles($project))->firstWhere('cover_attachment_id', $attachment['id']);
        if ($existing) {
            $cover = $this->fileCover($project, $existing['id']);
            if (is_array($cover)) {
                return $cover;
            }
        }

        $path = Storage::disk('local')->path($attachment['path']);
        $image = @getimagesize($path);
        $extension = ProjectCovers::IMAGE_EXTENSIONS[$image['mime'] ?? ''] ?? null;
        if (!$extension || $image[0] < 1 || $image[1] < 1 || $image[0] * $image[1] > 40000000) {
            return Response::error('Usa una imagen JPG, PNG, WebP o GIF válida de hasta 40 megapíxeles.');
        }
        $name = Str::slug(pathinfo($attachment['name'], PATHINFO_FILENAME)) ?: 'portada';
        $upload = new UploadedFile($path, "$name.$extension", null, UPLOAD_ERR_OK, true);
        if ($upload->getMimeType() !== $image['mime']) {
            return Response::error('El tipo de archivo no coincide con el contenido de la imagen.');
        }

        return $upload;
    }
}
