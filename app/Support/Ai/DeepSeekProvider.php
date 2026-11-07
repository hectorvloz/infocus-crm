<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;

class DeepSeekProvider implements AiProvider, AiToolCallingProvider
{
    use ParsesToolCalls;

    /**
     * Con herramientas el modo "pensar" va apagado: así no hay que reenviar el
     * razonamiento en cada vuelta y las respuestas llegan más rápido.
     */
    public function chatWithTools(array $messages, array $tools, array $settings): array
    {
        $response = Http::withToken((string) ($settings['api_key'] ?? ''))
            ->timeout(60)
            ->acceptJson()
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-flash',
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'thinking' => ['type' => 'disabled'],
                'temperature' => (float) ($settings['temperature'] ?? 0.4),
                'stream' => false,
            ]);

        $response->throw();

        return $this->parseToolResponse((array) $response->json());
    }

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
