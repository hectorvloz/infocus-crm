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
                'messages' => self::withoutFiles($messages),
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
                'messages' => self::withoutFiles($messages),
                'thinking' => ['type' => $thinking ? 'enabled' : 'disabled'],
                'temperature' => (float) ($settings['temperature'] ?? 0.4),
                'stream' => false,
            ]);

        $response->throw();
        return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
    }

    /** DeepSeek no lee PDF: cambia cada archivo por una nota para que el modelo sepa que llegó. */
    private static function withoutFiles(array $messages): array
    {
        return array_map(function (array $message) {
            if (!is_array($message['content'] ?? null)) {
                return $message;
            }
            $message['content'] = array_values(array_map(fn ($part) => ($part['type'] ?? '') === 'file'
                ? ['type' => 'text', 'text' => '[PDF adjunto: ' . data_get($part, 'file.filename', 'documento') . '. Con DeepSeek no puedo leer su contenido; sí puedo adjuntarlo a un correo o como comprobante.]']
                : $part, $message['content']));

            return $message;
        }, $messages);
    }
}
