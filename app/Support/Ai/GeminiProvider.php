<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProvider
{
    public function chat(array $messages, array $settings): string
    {
        $apiKey = (string) ($settings['api_key'] ?? '');
        $model = (string) ($settings['model'] ?? 'gemini-2.5-flash');

        $contents = collect($messages)
            ->filter(fn ($message) => ($message['role'] ?? '') !== 'system')
            ->map(fn ($message) => [
                'role' => ($message['role'] ?? '') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ])
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
