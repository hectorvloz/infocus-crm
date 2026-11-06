<?php

namespace App\Support\Ai;

use Illuminate\Support\Str;

/**
 * Skills del agente: guías de trabajo en resources/ai-skills/*.md que se cargan solo cuando
 * el pedido o la pantalla las necesitan, para que el agente trabaje con el método de Indigo.
 */
class AiSkills
{
    private const MAX_MATCHED = 2;

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $cache = null;

    public static function directory(): string
    {
        return resource_path('ai-skills');
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $skills = [];
        foreach (glob(self::directory() . '/*.md') ?: [] as $file) {
            $skill = self::parse((string) file_get_contents($file));
            if ($skill) {
                $skills[] = $skill;
            }
        }

        return self::$cache = $skills;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @return array<string, mixed>|null */
    public static function parse(string $raw): ?array
    {
        if (!preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', $raw, $m)) {
            return null;
        }
        $meta = [];
        foreach (preg_split('/\R/', $m[1]) ?: [] as $line) {
            if (!preg_match('/^([a-z_]+):\s*(.*)$/i', trim($line), $kv)) {
                continue;
            }
            $value = trim($kv[2]);
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $value = array_values(array_filter(array_map('trim', explode(',', substr($value, 1, -1))), fn ($v) => $v !== ''));
            } elseif (in_array(Str::lower($value), ['true', 'false'], true)) {
                $value = Str::lower($value) === 'true';
            }
            $meta[Str::lower($kv[1])] = $value;
        }
        if (empty($meta['name'])) {
            return null;
        }

        return [
            'name' => (string) $meta['name'],
            'title' => (string) ($meta['title'] ?? $meta['name']),
            'always' => (bool) ($meta['always'] ?? false),
            'triggers' => array_map(fn ($t) => self::normalize($t), (array) ($meta['triggers'] ?? [])),
            'sections' => (array) ($meta['sections'] ?? []),
            'body' => trim($m[2]),
        ];
    }

    private static function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }

    /**
     * Skills para este pedido: las fijas más las que mejor coinciden con el mensaje (y la sección abierta).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $message, string $path = ''): array
    {
        $text = ' ' . preg_replace('/\s+/', ' ', self::normalize($message)) . ' ';
        $always = [];
        $scored = [];
        foreach (self::all() as $skill) {
            if ($skill['always']) {
                $always[] = $skill;
                continue;
            }
            $score = 0.0;
            foreach ($skill['triggers'] as $trigger) {
                if ($trigger !== '' && preg_match('/(?<![a-z0-9])' . preg_quote($trigger, '/') . '/u', $text)) {
                    $score += 1 + mb_strlen($trigger) / 20;
                }
            }
            foreach ($skill['sections'] as $section) {
                if ($path !== '' && str_starts_with($path, (string) $section)) {
                    $score += $score > 0 ? 1 : 0.4;
                }
            }
            if ($score > 0) {
                $scored[] = [$score, $skill];
            }
        }
        usort($scored, fn ($a, $b) => $b[0] <=> $a[0]);
        // Una skill que solo coincide por la sección abierta (sin palabras del pedido) no desplaza a las que sí coinciden.
        $matched = array_map(fn ($row) => $row[1], array_filter(array_slice($scored, 0, self::MAX_MATCHED), fn ($row) => $row[0] >= 0.4));

        return array_merge($always, $matched);
    }

    public static function promptBlock(array $skills): string
    {
        if (!$skills) {
            return '';
        }

        return "Guías de trabajo de Indigo para este pedido (síguelas; no las menciones ni las cites al usuario):\n\n"
            . implode("\n\n", array_map(fn ($s) => '### ' . $s['title'] . "\n" . $s['body'], $skills));
    }
}
