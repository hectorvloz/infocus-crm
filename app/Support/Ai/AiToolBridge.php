<?php

namespace App\Support\Ai;

use App\Mcp\Support\McpContext;
use App\Mcp\Tools;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Throwable;

/**
 * Expone las herramientas del servidor MCP al asistente interno como funciones
 * que el modelo puede llamar. Se ejecutan dentro de Infocus con el usuario de la
 * sesión, sus permisos y el mismo registro de uso que el MCP.
 *
 * Fase 1: solo herramientas de lectura, y siempre con un contexto de solo lectura
 * para que una herramienta que intente escribir falle aunque se agregue por error.
 */
class AiToolBridge
{
    public const CONNECTION = 'asistente';

    private const MAX_RESULT_CHARS = 12000;

    /** @var array<int, class-string<Tool>> */
    private const READ_TOOLS = [
        Tools\ResumenNegocio::class,
        Tools\BuscarClientes::class,
        Tools\VerCliente::class,
        Tools\ListarLeads::class,
        Tools\VerLead::class,
        Tools\BuscarFacturas::class,
        Tools\VerFactura::class,
        Tools\ListarGastos::class,
        Tools\ListarProyectos::class,
        Tools\VerProyecto::class,
        Tools\VerTarea::class,
        Tools\ListarTareas::class,
        Tools\VerContrato::class,
        Tools\VerGuia::class,
        Tools\VerRecordatorios::class,
        Tools\ResumenSemanal::class,
        Tools\AgendaReuniones::class,
        Tools\BuscarContratos::class,
        Tools\VerPlantillasCorreo::class,
        Tools\ListarNotas::class,
        Tools\VerNota::class,
    ];

    /** @var array<string, Tool>|null */
    private ?array $tools = null;

    /**
     * Definiciones en formato de funciones de OpenAI/DeepSeek.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return array_values(array_map(function (Tool $tool) {
            $definition = $tool->toArray();

            return [
                'type' => 'function',
                'function' => [
                    'name' => $definition['name'],
                    'description' => (string) ($definition['description'] ?? ''),
                    'parameters' => $definition['inputSchema'] ?? ['type' => 'object', 'properties' => (object) []],
                ],
            ];
        }, $this->tools()));
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->tools());
    }

    /**
     * Ejecuta una herramienta y devuelve su resultado como texto para el modelo.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function call(string $name, array $arguments): string
    {
        $tool = $this->tools()[$name] ?? null;
        if (!$tool) {
            return 'ERROR: la herramienta ' . $name . ' no está disponible en el asistente.';
        }

        $previous = app()->bound(McpContext::class) ? app(McpContext::class) : null;
        app()->instance(McpContext::class, new McpContext(self::CONNECTION, 'lectura'));

        try {
            $response = $tool->handle(new Request($arguments));
            $text = $this->responseText($response);
        } catch (Throwable $e) {
            report($e);
            $text = 'ERROR: la herramienta falló. Intenta con otros datos o dile al usuario que revise Infocus.';
        } finally {
            if ($previous) {
                app()->instance(McpContext::class, $previous);
            } else {
                app()->forgetInstance(McpContext::class);
            }
        }

        return mb_strlen($text) > self::MAX_RESULT_CHARS
            ? mb_substr($text, 0, self::MAX_RESULT_CHARS) . "\n[Resultado recortado: pide algo más específico.]"
            : $text;
    }

    private function responseText(mixed $response): string
    {
        $responses = match (true) {
            $response instanceof Response => [$response],
            $response instanceof ResponseFactory => $response->responses()->all(),
            default => [],
        };

        $isError = false;
        $parts = [];
        foreach ($responses as $item) {
            $isError = $isError || $item->isError();
            $parts[] = (string) $item->content();
        }
        $text = trim(implode("\n", $parts));

        return $isError ? 'ERROR: ' . $text : $text;
    }

    /** @return array<string, Tool> */
    private function tools(): array
    {
        if ($this->tools === null) {
            $this->tools = [];
            foreach (self::READ_TOOLS as $class) {
                $tool = app($class);
                $this->tools[$tool->name()] = $tool;
            }
        }

        return $this->tools;
    }
}
