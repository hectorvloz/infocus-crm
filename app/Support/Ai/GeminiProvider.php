<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProvider, AiToolCallingProvider
{
    /**
     * Recibe y devuelve mensajes en formato OpenAI (el que usa AiService) y los
     * traduce al de Gemini: functionCall / functionResponse.
     */
    public function chatWithTools(array $messages, array $tools, array $settings): array
    {
        $apiKey = (string) ($settings['api_key'] ?? '');
        $payload = [
            'contents' => $this->toolContents($messages),
            'tools' => [['functionDeclarations' => array_map(function (array $tool) {
                $declaration = [
                    'name' => (string) data_get($tool, 'function.name'),
                    'description' => (string) data_get($tool, 'function.description', ''),
                ];
                $parameters = json_decode(json_encode(data_get($tool, 'function.parameters', [])), true) ?: [];
                if (!empty($parameters['properties'])) {
                    $declaration['parameters'] = $parameters;
                }

                return $declaration;
            }, $tools)]],
            'generationConfig' => ['temperature' => (float) ($settings['temperature'] ?? 0.4)],
        ];
        $systemPrompt = collect($messages)->where('role', 'system')->pluck('content')->filter()->implode("\n\n");
        if ($systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $lastResponse = null;
        foreach ($this->candidateModels((string) ($settings['model'] ?? 'gemini-2.5-flash')) as $candidateModel) {
            $response = Http::timeout(60)
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$candidateModel}:generateContent?key={$apiKey}", $payload);

            if ($response->successful()) {
                return $this->parseGeminiToolResponse((array) $response->json());
            }

            $lastResponse = $response;
            $message = (string) data_get($response->json(), 'error.message', '');
            if (!($response->status() === 404 || str_contains(strtolower($message), 'not found'))) {
                break;
            }
        }

        $lastResponse?->throw();

        return ['content' => '', 'tool_calls' => [], 'message' => ['role' => 'assistant', 'content' => '']];
    }

    /** @return array<int, array<string, mixed>> */
    private function toolContents(array $messages): array
    {
        $contents = [];
        $names = [];
        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? '');
            if ($role === 'system') {
                continue;
            }

            if ($role === 'tool') {
                $part = ['functionResponse' => [
                    'name' => $names[(string) ($message['tool_call_id'] ?? '')] ?? 'herramienta',
                    'response' => ['content' => (string) ($message['content'] ?? '')],
                ]];
                $last = array_key_last($contents);
                if ($last !== null && ($contents[$last]['_tool'] ?? false)) {
                    $contents[$last]['parts'][] = $part;
                } else {
                    $contents[] = ['role' => 'user', 'parts' => [$part], '_tool' => true];
                }
                continue;
            }

            if ($role === 'assistant') {
                $parts = [];
                if (trim((string) ($message['content'] ?? '')) !== '') {
                    $parts[] = ['text' => (string) $message['content']];
                }
                foreach ((array) ($message['tool_calls'] ?? []) as $call) {
                    $names[(string) $call['id']] = (string) data_get($call, 'function.name');
                    $args = json_decode((string) data_get($call, 'function.arguments', '{}'), true);
                    $part = ['functionCall' => ['name' => (string) data_get($call, 'function.name'), 'args' => (object) (is_array($args) ? $args : [])]];
                    if (!empty($call['thought_signature'])) {
                        $part['thoughtSignature'] = (string) $call['thought_signature'];
                    }
                    $parts[] = $part;
                }
                $contents[] = ['role' => 'model', 'parts' => $parts ?: [['text' => '']]];
                continue;
            }

            $contents[] = ['role' => 'user', 'parts' => $this->userParts($message['content'] ?? '')];
        }

        return array_map(function (array $content) {
            unset($content['_tool']);

            return $content;
        }, $contents);
    }

    /** @return array<int, array<string, mixed>> */
    private function userParts(mixed $content): array
    {
        $parts = [];
        foreach (is_array($content) ? $content : [['type' => 'text', 'text' => (string) $content]] as $part) {
            if (($part['type'] ?? '') === 'text') {
                $parts[] = ['text' => (string) ($part['text'] ?? '')];
            } elseif (in_array($part['type'] ?? '', ['image_url', 'file'], true)) {
                $url = (string) (data_get($part, 'image_url.url') ?? data_get($part, 'file.file_data', ''));
                if (preg_match('/^data:(image\/(?:jpeg|png|gif|webp)|application\/pdf);base64,(.+)$/s', $url, $match)) {
                    $parts[] = ['inlineData' => ['mimeType' => $match[1], 'data' => $match[2]]];
                }
            }
        }

        return $parts;
    }

    /** @return array{content:string, tool_calls:array<int, array{id:string, name:string, arguments:array<string, mixed>}>, message:array<string, mixed>} */
    private function parseGeminiToolResponse(array $body): array
    {
        $text = [];
        $calls = [];
        $rawCalls = [];
        foreach ((array) data_get($body, 'candidates.0.content.parts', []) as $index => $part) {
            if (isset($part['text']) && empty($part['thought'])) {
                $text[] = (string) $part['text'];
            }
            if (!empty($part['functionCall']['name'])) {
                $id = (string) ($part['functionCall']['id'] ?? ('gemini_' . $index . '_' . substr(md5((string) json_encode($part['functionCall'])), 0, 8)));
                $args = is_array($part['functionCall']['args'] ?? null) ? $part['functionCall']['args'] : [];
                $calls[] = ['id' => $id, 'name' => (string) $part['functionCall']['name'], 'arguments' => $args];
                $raw = ['id' => $id, 'type' => 'function', 'function' => ['name' => (string) $part['functionCall']['name'], 'arguments' => json_encode((object) $args)]];
                if (!empty($part['thoughtSignature'])) {
                    $raw['thought_signature'] = (string) $part['thoughtSignature'];
                }
                $rawCalls[] = $raw;
            }
        }

        $content = trim(implode("\n", $text));
        $message = ['role' => 'assistant', 'content' => $content];
        if ($rawCalls) {
            $message['tool_calls'] = $rawCalls;
        }

        return ['content' => $content, 'tool_calls' => $calls, 'message' => $message];
    }

    public function chat(array $messages, array $settings): string
    {
        $apiKey = (string) ($settings['api_key'] ?? '');
        $model = (string) ($settings['model'] ?? 'gemini-2.5-flash');

        $contents = collect($messages)
            ->filter(fn ($message) => ($message['role'] ?? '') !== 'system')
            ->map(function ($message) {
                $content = $message['content'] ?? '';
                $parts = [];
                foreach (is_array($content) ? $content : [['type' => 'text', 'text' => (string) $content]] as $part) {
                    if (($part['type'] ?? '') === 'text') {
                        $parts[] = ['text' => (string) ($part['text'] ?? '')];
                    } elseif (in_array($part['type'] ?? '', ['image_url', 'file'], true)) {
                        $url = (string) (data_get($part, 'image_url.url') ?? data_get($part, 'file.file_data', ''));
                        if (preg_match('/^data:(image\/(?:jpeg|png|gif|webp)|application\/pdf);base64,(.+)$/s', $url, $match)) {
                            $parts[] = ['inlineData' => ['mimeType' => $match[1], 'data' => $match[2]]];
                        }
                    }
                }
                return [
                    'role' => ($message['role'] ?? '') === 'assistant' ? 'model' : 'user',
                    'parts' => $parts,
                ];
            })
            ->values()
            ->all();

        $systemPrompt = collect($messages)
            ->where('role', 'system')
            ->pluck('content')
            ->filter()
            ->implode("\n\n");

        $payload = ['contents' => $contents];
        if ($systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $lastResponse = null;
        foreach ($this->candidateModels($model) as $candidateModel) {
            $response = Http::timeout(45)
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$candidateModel}:generateContent?key={$apiKey}", $payload);

            if ($response->successful()) {
                return trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));
            }

            $lastResponse = $response;
            $message = (string) data_get($response->json(), 'error.message', '');
            $modelMissing = $response->status() === 404 || str_contains(strtolower($message), 'not found');
            if (! $modelMissing) {
                break;
            }
        }

        $lastResponse?->throw();

        return '';
    }

    private function candidateModels(string $model): array
    {
        $model = trim($model);
        if ($model === '' || $model === 'auto' || str_starts_with($model, 'gemini-1.5-')) {
            $model = 'gemini-2.5-flash';
        }

        return array_values(array_unique(array_filter([
            $model,
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.0-flash',
        ])));
    }
}
