<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Mcp\Support\McpDenied;
use App\Repositories\FileStore;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Base de las herramientas de Infocus: convierte permisos y validaciones en mensajes
 * claros para el asistente y deja registro de cada uso en mcp_activity.json.
 */
abstract class InfocusTool extends Tool
{
    abstract protected function run(Request $request, McpContext $ctx): Response;

    public function handle(Request $request): Response
    {
        $ctx = McpContext::current();
        try {
            $response = $this->run($request, $ctx);
            $ctx->log($this->name(), $response->isError() ? 'error' : 'ok');

            return $response;
        } catch (McpDenied $e) {
            $ctx->log($this->name(), 'denegado: ' . $e->getMessage());

            return Response::error($e->getMessage());
        } catch (ValidationException $e) {
            return Response::error('Datos inválidos: ' . collect($e->errors())->flatten()->implode(' '));
        } catch (HttpException $e) {
            return Response::error($e->getStatusCode() === 404 ? 'No encontré ese registro.' : 'No se pudo completar la acción.');
        } catch (Throwable $e) {
            report($e);
            $ctx->log($this->name(), 'falló');

            return Response::error('Ocurrió un error interno. Revisa el log de Infocus.');
        }
    }

    protected function store(string $file): FileStore
    {
        return new FileStore($file);
    }

    /** Busca texto sin importar mayúsculas ni tildes. */
    protected function matches(?string $haystack, string $needle): bool
    {
        $norm = fn (string $s) => Str::lower(Str::ascii($s));

        return $needle === '' || str_contains($norm((string) $haystack), $norm($needle));
    }

    /** Marca contenido escrito por terceros para que el asistente no lo trate como instrucciones. */
    protected function external(?string $text): string
    {
        $text = trim(strip_tags((string) $text));

        return $text === '' ? '' : "«{$text}»";
    }

    /** Llama a un método de controlador existente para reutilizar exactamente la lógica de la app. */
    protected function callController(string $controller, string $method, array $input): array
    {
        $request = HttpRequest::create('/', 'POST', $input);
        $request->setUserResolver(fn () => auth()->user());
        app()->instance('request', $request);
        $response = app($controller)->{$method}($request);
        $data = method_exists($response, 'getData') ? (array) $response->getData(true) : [];

        return ['status' => $response->getStatusCode(), 'data' => $data];
    }

    /** Hace la escritura guardando antes una copia para poder restaurarla con restaurar_cambio. */
    protected function tracked(string $file, string $recordId, string $label, callable $write): mixed
    {
        return (new \App\Mcp\Support\McpHistory())->track($file, $recordId, $label, $write, McpContext::current()->connection);
    }

    protected function money(float|int|string|null $amount, ?string $currency): string
    {
        return trim(($currency ?: '') . ' ' . number_format((float) $amount, 0, ',', '.'));
    }
}
