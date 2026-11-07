<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Tareas programadas del asistente, por usuario, en ai_scheduled_tasks.json.
 * - aviso: manda un texto fijo a la hora indicada ("llamar a Laura").
 * - consulta: el asistente ejecuta una instrucción con herramientas de solo lectura
 *   ("revisa quién me debe") y manda el resultado.
 * No se borran: se desactivan.
 */
class ScheduledTasks
{
    public const FILE = 'ai_scheduled_tasks.json';

    public const TZ = 'America/Bogota';

    public const DAYS = ['lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'domingo' => 7];

    public static function store(): FileStore
    {
        return new FileStore(self::FILE);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(?string $userId = null): array
    {
        $userId ??= (string) Auth::id();

        return array_values(array_filter(self::store()->all(), fn ($t) => (string) ($t['user_id'] ?? '') === $userId));
    }

    /** @return array<int, int> */
    public static function parseDays(array $days): array
    {
        $out = [];
        foreach ($days as $day) {
            $key = Str::lower(Str::ascii(trim((string) $day)));
            if (isset(self::DAYS[$key])) {
                $out[] = self::DAYS[$key];
            } elseif (ctype_digit($key) && (int) $key >= 1 && (int) $key <= 7) {
                $out[] = (int) $key;
            }
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /** Próxima ejecución en hora de Colombia, después de $after; null si ya no hay más. */
    public static function nextRun(array $task, CarbonImmutable $after): ?CarbonImmutable
    {
        $after = $after->setTimezone(self::TZ);
        [$h, $m] = array_map('intval', explode(':', (string) ($task['hora'] ?? '08:00')) + [0, 0]);
        $start = !empty($task['fecha']) ? CarbonImmutable::parse($task['fecha'], self::TZ)->startOfDay() : $after->startOfDay();
        $base = $start->greaterThan($after->startOfDay()) ? $start : $after->startOfDay();

        switch ($task['frecuencia'] ?? 'una_vez') {
            case 'una_vez':
                $at = $start->setTime($h, $m);

                return $at->greaterThan($after) ? $at : null;
            case 'diaria':
                $at = $base->setTime($h, $m);

                return $at->greaterThan($after) ? $at : $at->addDay();
            case 'semanal':
                $days = $task['dias'] ?? [];
                for ($i = 0; $i < 15 && $days; $i++) {
                    $at = $base->addDays($i)->setTime($h, $m);
                    if (in_array($at->dayOfWeekIso, $days, true) && $at->greaterThan($after)) {
                        return $at;
                    }
                }

                return null;
            case 'mensual':
                $day = max(1, min(31, (int) ($task['dia_mes'] ?? 1)));
                for ($i = 0; $i < 3; $i++) {
                    $month = $base->startOfMonth()->addMonths($i);
                    $at = $month->setDay(min($day, $month->daysInMonth))->setTime($h, $m);
                    if ($at->greaterThan($after) && !$at->lessThan($start)) {
                        return $at;
                    }
                }

                return null;
        }

        return null;
    }

    public static function describe(array $task): string
    {
        $when = match ($task['frecuencia'] ?? 'una_vez') {
            'una_vez' => 'el ' . ($task['fecha'] ?? '?'),
            'diaria' => 'todos los días',
            'semanal' => 'cada ' . implode(', ', array_map(fn ($d) => array_search($d, self::DAYS, true) ?: $d, $task['dias'] ?? [])),
            'mensual' => 'el día ' . ($task['dia_mes'] ?? 1) . ' de cada mes',
            default => '',
        };
        $next = !empty($task['proxima']) ? CarbonImmutable::parse($task['proxima'])->setTimezone(self::TZ)->format('Y-m-d H:i') : 'sin próxima fecha';

        return sprintf('- %s (id: %s) · %s · %s a las %s por %s · %s · próxima: %s',
            ($task['tipo'] ?? 'aviso') === 'consulta' ? 'Consulta' : 'Aviso',
            $task['id'],
            '«' . Str::limit((string) ($task['texto'] ?? ''), 120) . '»',
            $when,
            $task['hora'] ?? '08:00',
            ($task['canal'] ?? 'whatsapp') === 'correo' ? 'correo' : 'WhatsApp',
            !empty($task['activa']) ? 'activa' : 'pausada',
            $next,
        );
    }
}
