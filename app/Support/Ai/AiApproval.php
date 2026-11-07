<?php

namespace App\Support\Ai;

use Illuminate\Support\Str;

/**
 * Decide si el mensaje del usuario aprueba de forma explícita una vista previa.
 * Es estricto a propósito: ante la duda, no es aprobación y el asistente vuelve a preguntar.
 */
class AiApproval
{
    private const YES = [
        'si', 'dale', 'confirmo', 'confirmado', 'confirmar', 'apruebo', 'aprobado', 'adelante', 'hazlo', 'listo',
        'ok', 'okay', 'de una', 'claro', 'perfecto', 'correcto', 'envialo', 'enviala', 'mandalo', 'mandala',
        'registralo', 'registrala', 'programala', 'programalo', 'agendala', 'agendalo',
    ];

    private const DOUBT = ['no', 'pero', 'cambia', 'cambiale', 'corrige', 'espera', 'antes', 'mejor', 'todavia', 'aun', 'quita', 'agrega', 'ajusta'];

    public static function isExplicit(string $message): bool
    {
        $text = Str::of(Str::ascii($message))->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->value();
        if ($text === '' || mb_strlen($text) > 80) {
            return false;
        }

        $words = explode(' ', $text);
        foreach (self::DOUBT as $doubt) {
            if (in_array($doubt, $words, true)) {
                return false;
            }
        }

        foreach (self::YES as $yes) {
            if ($text === $yes || str_starts_with($text, $yes . ' ')) {
                return true;
            }
        }

        return false;
    }
}
