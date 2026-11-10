<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Support\ProjectCovers;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_portadas_proyecto')]
#[Description('Lista las imágenes y colores de la galería de Infocus. Con proyecto_id, muestra también la portada actual y las imágenes ya adjuntas al proyecto para reutilizarlas con gestionar_portada_proyecto.')]
#[IsReadOnly]
class ListarPortadasProyecto extends InfocusTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['proyecto_id' => $schema->string()->description('Opcional. Usa listar_proyectos para obtener el ID.')];
    }

    protected function run(Request $request, McpContext $ctx): Response
    {
        $ctx->allow('proyectos.read');
        $d = $request->validate(['proyecto_id' => 'nullable|string|max:100']);
        $lines = [];
        if (!empty($d['proyecto_id'])) {
            $project = $this->store('proyectos.json')->find($d['proyecto_id']);
            abort_if(!$project, 404);
            $files = ProjectCovers::imageFiles($project);
            $image = (string) ($project['cover_image'] ?? '');
            $path = $image === '' ? '' : parse_url($image, PHP_URL_PATH);
            $preset = collect(ProjectCovers::PRESETS)->firstWhere('url', $path);
            $file = collect($files)->first(fn ($item) => ProjectCovers::documentPreview($item['id']) === $path);
            $color = (string) ($project['cover_color'] ?? '');
            $current = match (true) {
                $preset !== null => $preset['name'],
                $file !== null => $this->external($file['name'] ?? 'Imagen adjunta'),
                $image !== '' => 'Imagen personalizada',
                $color !== '' => $this->external($color),
                default => 'Predeterminada',
            };
            $lines[] = 'Proyecto ' . $this->external($project['titulo'] ?? '') . ' · Portada actual: ' . $current;
            $lines[] = 'Imágenes del proyecto (acción archivo):';
            foreach ($files as $item) {
                $lines[] = '- ' . $this->external($item['name'] ?? 'Imagen') . ' · ' . $item['id'];
            }
            if (!$files) {
                $lines[] = '- Sin imágenes adjuntas.';
            }
            $lines[] = '';
        }

        $lines[] = 'Galería (acción galeria, usa el nombre): ' . implode(', ', array_column(ProjectCovers::PRESETS, 'name'));
        $lines[] = 'Colores (acción color, usa uno o dos hexadecimales): ' . implode(', ', array_map(fn ($pair) => implode('|', $pair), ProjectCovers::PALETTES));

        return Response::text(implode("\n", $lines));
    }
}
