<?php

namespace App\Support\WhatsApp;

use App\Repositories\FileStore;
use Illuminate\Support\Str;

/**
 * Registro corto de lo que pasa con los mensajes que llegan por WhatsApp
 * (recibido, rechazado, procesado, error), para ver en Configuración dónde se
 * pierde un mensaje sin tener que leer el log del servidor. Nunca guarda claves.
 */
class WhatsAppEvents
{
    public const FILE = 'whatsapp_events.json';

    private const KEEP = 30;

    public static function record(string $provider, string $result, string $from = '', string $detail = ''): void
    {
        $store = new FileStore(self::FILE);
        $all = $store->all();
        $all[] = [
            'id' => (string) Str::ulid(),
            'at' => now()->toISOString(),
            'provider' => $provider,
            'from' => Str::limit($from, 60, ''),
            'result' => $result,
            'detail' => Str::limit($detail, 300, '…'),
        ];
        $store->save(array_slice($all, -self::KEEP));
    }

    /** @return array<int, array<string, string>> los más recientes primero */
    public static function latest(int $limit = 10): array
    {
        return array_slice(array_reverse((new FileStore(self::FILE))->all()), 0, $limit);
    }
}
