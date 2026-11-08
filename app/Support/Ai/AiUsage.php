<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;

/**
 * Cuenta las llamadas a la IA por día (modelo y transcripciones) y frena al llegar
 * al tope de Configuración > IA, para que un error o un bucle no dispare el costo.
 */
class AiUsage
{
    public const FILE = 'ai_usage.json';

    public const DEFAULT_LIMIT = 400;

    private const KEEP_DAYS = 31;

    public static function limit(): int
    {
        $s = (new FileStore('settings.json'))->find('settings') ?: [];
        $limit = (int) ($s['ai_daily_limit'] ?? self::DEFAULT_LIMIT);

        return $limit > 0 ? $limit : self::DEFAULT_LIMIT;
    }

    public static function today(): array
    {
        $row = (new FileStore(self::FILE))->find(now('America/Bogota')->toDateString()) ?: [];

        return ['modelo' => (int) ($row['modelo'] ?? 0), 'transcripcion' => (int) ($row['transcripcion'] ?? 0)];
    }

    public static function total(): int
    {
        return array_sum(self::today());
    }

    public static function reached(): bool
    {
        return self::total() >= self::limit();
    }

    public static function count(string $kind = 'modelo'): void
    {
        $store = new FileStore(self::FILE);
        $date = now('America/Bogota')->toDateString();
        $row = $store->find($date);
        if ($row) {
            $store->update($date, [$kind => (int) ($row[$kind] ?? 0) + 1]);

            return;
        }
        $all = array_values(array_filter($store->all(), fn ($r) => (string) ($r['id'] ?? '') >= now('America/Bogota')->subDays(self::KEEP_DAYS)->toDateString()));
        $all[] = ['id' => $date, $kind => 1];
        $store->save($all);
    }

    public static function limitMessage(): string
    {
        return 'Llegué al tope diario de ' . self::limit() . ' consultas a la IA que tienes configurado. Mañana sigo, o puedes subirlo en Configuración > IA.';
    }
}
