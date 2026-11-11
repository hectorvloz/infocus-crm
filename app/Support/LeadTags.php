<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Etiquetas de los leads (nichos como "RentaCar" o "Inmobiliarias").
 * Se guardan en cada lead como lista en el campo `etiquetas`.
 */
class LeadTags
{
    public const MAX_POR_LEAD = 15;
    public const MAX_LARGO = 40;

    /**
     * Limpia etiquetas que llegan como lista o como texto separado por comas.
     * Quita espacios de más y repetidas (sin importar mayúsculas o tildes).
     *
     * @param  array<int, mixed>|string|null  $input
     * @return array<int, string>
     */
    public static function normalize(array|string|null $input): array
    {
        $items = is_array($input) ? $input : explode(',', (string) $input);
        $seen = [];
        $tags = [];
        foreach ($items as $item) {
            $tag = trim(preg_replace('/\s+/u', ' ', (string) $item) ?? '');
            $tag = Str::limit($tag, self::MAX_LARGO, '');
            if ($tag === '') {
                continue;
            }
            $key = self::key($tag);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $tags[] = $tag;
            if (count($tags) >= self::MAX_POR_LEAD) {
                break;
            }
        }

        return $tags;
    }

    /**
     * Todas las etiquetas usadas en los leads, sin repetir y en orden alfabético.
     *
     * @param  array<int, array<string, mixed>>  $leads
     * @return array<int, string>
     */
    public static function all(array $leads): array
    {
        $byKey = [];
        foreach ($leads as $lead) {
            foreach (self::of($lead) as $tag) {
                $byKey[self::key($tag)] ??= $tag;
            }
        }
        $tags = array_values($byKey);
        usort($tags, fn ($a, $b) => strcmp(self::key($a), self::key($b)));

        return $tags;
    }

    /** @return array<int, string> */
    public static function of(array $lead): array
    {
        return self::normalize(is_array($lead['etiquetas'] ?? null) ? $lead['etiquetas'] : []);
    }

    public static function has(array $lead, string $tag): bool
    {
        $key = self::key($tag);

        return collect(self::of($lead))->contains(fn ($t) => self::key($t) === $key);
    }

    /** Clave para comparar: minúsculas y sin tildes. */
    public static function key(string $tag): string
    {
        return Str::lower(Str::ascii(trim($tag)));
    }
}
