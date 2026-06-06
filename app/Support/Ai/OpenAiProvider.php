<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProvider
{
    public function chat(array $messages, array $settings): string
    {
        $apiKey = (string) ($settings['api_key'] ?? '');
        $model = (string) ($settings['model'] ?? 'gpt-4o-mini');

        $lastResponse = null;
        foreach ($this->candidateModels($model) as $candidateModel) {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $candidateModel,
                    'messages' => $messages,
                    'temperature' => (float) ($settings['temperature'] ?? 0.4),
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
        if ($model === '' || $model === 'auto') {
            $model = 'gpt-4o-mini';
        }

        return array_values(array_unique(array_filter([
            $model,
            'gpt-4o-mini',
            'gpt-4.1-mini',
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
