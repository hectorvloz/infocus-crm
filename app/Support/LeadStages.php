<?php

namespace App\Support;

/**
 * Etapas del tablero de leads y papelera (leads archivados).
 * "No interesado" es la zona roja: el lead se conserva pero no se le vuelve a contactar.
 * Un lead archivado sale del tablero y queda en la papelera, desde donde se restaura.
 */
class LeadStages
{
    public const NO_INTERESADO = 'No interesado';

    public const ETAPAS = ['Posible cliente', 'Contactado', 'Volver a llamar', 'Cliente', self::NO_INTERESADO];

    private const ALIAS = [
        'Nuevo' => 'Posible cliente',
        'Calificado' => 'Posible cliente',
        'Propuesta' => 'Contactado',
        'Llamar' => 'Volver a llamar',
        'Seguimiento' => 'Volver a llamar',
        'Ganado' => 'Cliente',
        'Perdido' => self::NO_INTERESADO,
    ];

    public static function normalize(?string $etapa): string
    {
        $etapa = trim((string) $etapa);
        if (in_array($etapa, self::ETAPAS, true)) {
            return $etapa;
        }

        return self::ALIAS[$etapa] ?? 'Posible cliente';
    }

    public static function archived(array $lead): bool
    {
        return !empty($lead['archivado_en']);
    }

    /** @param array<int, array<string, mixed>> $leads */
    public static function active(array $leads): array
    {
        return array_values(array_filter($leads, fn ($l) => is_array($l) && !self::archived($l)));
    }
}
