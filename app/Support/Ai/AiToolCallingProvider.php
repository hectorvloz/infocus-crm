<?php

namespace App\Support\Ai;

/**
 * Proveedor que puede pedir herramientas (function calling, formato OpenAI).
 */
interface AiToolCallingProvider
{
    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @return array{content:string, tool_calls:array<int, array{id:string, name:string, arguments:array<string, mixed>}>, message:array<string, mixed>}
     */
    public function chatWithTools(array $messages, array $tools, array $settings): array;
}
