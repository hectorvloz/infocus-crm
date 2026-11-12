<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Perfil del negocio de un lead: descripción, web y redes sociales.
 * Acepta enlaces completos o usuarios (@marca) y los guarda como enlaces.
 */
class LeadProfile
{
    public const REDES = [
        'sitio_web' => ['label' => 'Web', 'base' => null],
        'instagram' => ['label' => 'Instagram', 'base' => 'https://instagram.com/'],
        'facebook' => ['label' => 'Facebook', 'base' => 'https://facebook.com/'],
        'tiktok' => ['label' => 'TikTok', 'base' => 'https://tiktok.com/@'],
        'linkedin' => ['label' => 'LinkedIn', 'base' => 'https://linkedin.com/company/'],
    ];

    public const FIELDS = ['descripcion', 'sitio_web', 'instagram', 'facebook', 'tiktok', 'linkedin'];

    public static function rules(): array
    {
        $rules = ['descripcion' => 'nullable|string|max:2000'];
        foreach (array_keys(self::REDES) as $key) {
            $rules[$key] = 'nullable|string|max:300';
        }

        return $rules;
    }

    /** Solo los campos del perfil que vienen en $input, ya limpios. */
    public static function from(array $input): array
    {
        $out = [];
        if (array_key_exists('descripcion', $input)) {
            $out['descripcion'] = trim((string) $input['descripcion']) ?: null;
        }
        foreach (self::REDES as $key => $red) {
            if (array_key_exists($key, $input)) {
                $out[$key] = self::link((string) $input[$key], $red['base']);
            }
        }

        return $out;
    }

    public static function link(string $value, ?string $base): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }
        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }
        if (preg_match('~^(www\.|[a-z0-9-]+\.[a-z]{2,})~i', $value) && str_contains($value, '.')) {
            return 'https://' . ltrim($value, '/');
        }

        return $base ? $base . ltrim($value, '@/') : 'https://' . $value;
    }

    /** Texto para el MCP: una línea por dato que exista. */
    public static function lines(array $lead): array
    {
        $lines = [];
        if (!empty($lead['descripcion'])) {
            $lines[] = 'Descripción: «' . Str::limit((string) $lead['descripcion'], 600) . '»';
        }
        foreach (self::REDES as $key => $red) {
            if (!empty($lead[$key])) {
                $lines[] = $red['label'] . ': ' . $lead[$key];
            }
        }

        return $lines;
    }
}
