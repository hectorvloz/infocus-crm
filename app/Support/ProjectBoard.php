<?php

namespace App\Support;

class ProjectBoard
{
    public const DEFAULT_STAGES = ['Por hacer', 'En proceso', 'Revisión', 'Terminado'];

    public static function stages(array $project): array
    {
        return array_values(array_filter(array_map('trim', array_filter((array) ($project['task_stages'] ?? []), 'is_string')), 'strlen'))
            ?: self::DEFAULT_STAGES;
    }
}
