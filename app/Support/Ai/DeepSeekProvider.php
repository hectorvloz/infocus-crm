<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class DeepSeekProvider implements AiProvider
{
    public function chat(array $messages, array $settings): string
    {
        $apiKey = (string) ($settings['api_key'] ?? '');
        $model = (string) ($settings['model'] ?? 'deepseek-chat');

        $lastResponse = null;
        foreach ($this->candidateModels($model) as $candidateModel) {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->acceptJson()
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => $candidateModel,
                    'messages' => $messages,
                    'temperature' => (float) ($settings['temperature'] ?? 0.4),
                    'stream' => false,
                ]);

            if ($response->successful()) {
                return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            }

            $lastResponse = $response;
            if (! $this->looksLikeModelError($response->json(), $response->status())) {
                break;
            }
        }

        $lastResponse?->throw();

        return '';
    }

    private function candidateModels(string $model): array
    {
        $model = trim($model);
        if ($model === '' || $model === 'auto' || str_starts_with($model, 'gpt-') || str_starts_with($model, 'gemini-')) {
            $model = 'deepseek-chat';
        }

        return array_values(array_unique(array_filter([
            $model,
            'deepseek-chat',
            'deepseek-reasoner',
        ])));
    }

    private function looksLikeModelError(array $body, int $status): bool
    {
        if (! in_array($status, [400, 404], true)) {
            return false;
        }

        $message = strtolower((string) data_get($body, 'error.message', ''));
        $code = strtolower((string) data_get($body, 'error.code', ''));

        return str_contains($message, 'model') || str_contains($code, 'model');
    }
}
