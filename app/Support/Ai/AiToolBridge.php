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
 * Las de lectura corren con un contexto de solo lectura, para que si alguna intenta
 * escribir falle. Las de escritura directa son las de bajo riesgo, internas y
 * reversibles: recordatorios, notas, tareas y leads. Las que salen hacia afuera
 * (correos, facturas, pagos, reuniones, contratos) van en dos pasos y confirmar_accion
 * solo se ejecuta si el usuario aprobó explícitamente un código que ya vio.
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
        Tools\VerCambiosRecientes::class,
    ];

    /** @var array<int, class-string<Tool>> */
    private const WRITE_TOOLS = [
        Tools\CrearRecordatorio::class,
        Tools\CompletarRecordatorio::class,
        Tools\EditarRecordatorio::class,
        Tools\OrganizarRecordatorios::class,
        Tools\CrearNota::class,
        Tools\EditarNota::class,
        Tools\AgregarNotaCliente::class,
        Tools\AgregarNotaProyecto::class,
        Tools\CrearTarea::class,
        Tools\ActualizarTarea::class,
        Tools\AgregarSubtareas::class,
        Tools\ActualizarSubtarea::class,
        Tools\CrearLead::class,
        Tools\ActualizarLead::class,
        Tools\RestaurarCambio::class,
        // Dos pasos (fase 3): preparan una vista previa con código; solo confirmar_accion ejecuta.
        Tools\PrepararCorreo::class,
        Tools\PrepararEnvioFactura::class,
        Tools\PrepararPago::class,
        Tools\PrepararReunion::class,
        Tools\PrepararEnvioContrato::class,
        Tools\ConfirmarAccion::class,
    ];

    public const CONFIRM_TOOL = 'confirmar_accion';

    /** @var array<string, Tool>|null */
    private ?array $tools = null;

    /** @var array<int, string> */
    private array $writeNames = [];

    private bool $userApproved = false;

    /** @var array<int, string> */
    private array $shownCodes = [];

    /**
     * Qué puede confirmar el modelo en esta vuelta: solo códigos que el usuario ya vio
     * en la respuesta anterior, y solo si su mensaje actual es una aprobación explícita.
     *
     * @param  array<int, string>  $shownCodes
     */
    public function allowConfirmation(bool $userApproved, array $shownCodes): void
    {
        $this->userApproved = $userApproved;
        $this->shownCodes = array_values(array_map(fn ($c) => strtoupper(trim((string) $c)), $shownCodes));
    }

    public static function isPrepareTool(string $name): bool
    {
        return str_starts_with($name, 'preparar_');
    }

    /** @return array<int, string> */
    public static function codesIn(string $text): array
    {
        preg_match_all('/\b([A-Za-z0-9]{3}-[A-Za-z0-9]{3})\b/', $text, $matches);

        return array_values(array_unique(array_map('strtoupper', $matches[1] ?? [])));
    }

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

    public function writes(string $name): bool
    {
        $this->tools();

        return in_array($name, $this->writeNames, true);
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

        if ($name === self::CONFIRM_TOOL) {
            $code = strtoupper(trim((string) ($arguments['codigo'] ?? '')));
            if (!$this->userApproved) {
                return 'ERROR: no confirmes todavía. El usuario debe aprobar la vista previa con un mensaje explícito (por ejemplo "sí, envíalo"). Pídeselo.';
            }
            if (!in_array($code, $this->shownCodes, true)) {
                return 'ERROR: ese código no es el de la vista previa que el usuario acaba de ver. Prepara la acción de nuevo y muéstrale la vista previa.';
            }
        }

        $previous = app()->bound(McpContext::class) ? app(McpContext::class) : null;
        app()->instance(McpContext::class, new McpContext(self::CONNECTION, $this->writes($name) ? 'completo' : 'lectura'));

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
            foreach ([...self::READ_TOOLS, ...self::WRITE_TOOLS] as $class) {
                $tool = app($class);
                $this->tools[$tool->name()] = $tool;
                if (in_array($class, self::WRITE_TOOLS, true)) {
                    $this->writeNames[] = $tool->name();
                }
            }
        }

        return $this->tools;
    }
}
