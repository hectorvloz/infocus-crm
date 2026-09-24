<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class DeepSeekProvider implements AiProvider
{
    public function chat(array $messages, array $settings): string
    {
        $apiKey = (string) ($settings['api_key'] ?? '');
        $model = trim((string) ($settings['model'] ?? 'deepseek-flash'));
        $thinking = in_array($model, ['deepseek-reasoner', 'deepseek-flash-thinking'], true);

        $response = Http::withToken($apiKey)
            ->timeout(45)
            ->acceptJson()
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-flash',
                'messages' => $messages,
                'thinking' => ['type' => $thinking ? 'enabled' : 'disabled'],
                'temperature' => (float) ($settings['temperature'] ?? 0.4),
                'stream' => false,
            ]);

        $response->throw();
        return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
    }
}
