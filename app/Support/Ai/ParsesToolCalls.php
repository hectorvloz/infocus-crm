<?php

namespace App\Support\Ai;

/**
 * Lee la respuesta de una API con formato de chat de OpenAI (OpenAI y DeepSeek)
 * cuando el modelo puede pedir herramientas.
 */
trait ParsesToolCalls
{
    /**
     * @param  array<string, mixed>  $body
     * @return array{content:string, tool_calls:array<int, array{id:string, name:string, arguments:array<string, mixed>}>, message:array<string, mixed>}
     */
    private function parseToolResponse(array $body): array
    {
        $message = (array) data_get($body, 'choices.0.message', []);
        $calls = [];
        $rawCalls = [];

        foreach ((array) ($message['tool_calls'] ?? []) as $call) {
            $id = (string) data_get($call, 'id', '');
            $name = (string) data_get($call, 'function.name', '');
            if ($id === '' || $name === '') {
                continue;
            }
            $rawArguments = (string) data_get($call, 'function.arguments', '');
            $arguments = json_decode($rawArguments !== '' ? $rawArguments : '{}', true);
            $calls[] = ['id' => $id, 'name' => $name, 'arguments' => is_array($arguments) ? $arguments : []];
            $rawCalls[] = [
                'id' => $id,
                'type' => 'function',
                'function' => ['name' => $name, 'arguments' => $rawArguments !== '' ? $rawArguments : '{}'],
            ];
        }

        $content = trim((string) ($message['content'] ?? ''));
        $assistant = ['role' => 'assistant', 'content' => $content];
        if ($rawCalls) {
            $assistant['tool_calls'] = $rawCalls;
        }

        return ['content' => $content, 'tool_calls' => $calls, 'message' => $assistant];
    }
}
