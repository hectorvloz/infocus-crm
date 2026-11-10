<?php

namespace App\Support;

class ProjectCovers
{
    public const IMAGE_EXTENSIONS = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
    ];

    public const PALETTES = [
        ['#1e1b4b', '#6d28d9'],
        ['#be185d', '#f97316'],
        ['#0c4a6e', '#0891b2'],
        ['#0f172a', '#334155'],
        ['#312e81', '#2563eb'],
        ['#134e4a', '#0d9488'],
        ['#4338ca', '#ec4899'],
        ['#7c2d12', '#ea580c'],
        ['#064e3b', '#059669'],
        ['#831843', '#db2777'],
        ['#0369a1', '#7c3aed'],
        ['#9a3412', '#f59e0b'],
        ['#4c1d95', '#a855f7'],
        ['#881337', '#e11d48'],
        ['#1e3a8a', '#6366f1'],
        ['#0f766e', '#22d3ee'],
        ['#701a75', '#c026d3'],
        ['#065f46', '#0284c7'],
        ['#78350f', '#d97706'],
        ['#7f1d1d', '#dc2626'],
        ['#1f2937', '#6b7280'],
        ['#3f3f46', '#a1a1aa'],
        ['#18181b', '#52525b'],
        ['#14532d', '#4d7c0f'],
    ];

    public const PRESETS = [
        ['name' => 'Índigo profundo', 'url' => '/images/project-covers/gen-indigo-profundo.webp'],
        ['name' => 'Atardecer', 'url' => '/images/project-covers/gen-atardecer.webp'],
        ['name' => 'Neon suave', 'url' => '/images/project-covers/photo-1550745165-9bc0b252726f.webp', 'legacyId' => 'photo-1550745165-9bc0b252726f'],
        ['name' => 'Prisma', 'url' => '/images/project-covers/photo-1557683316-973673baf926.webp', 'legacyId' => 'photo-1557683316-973673baf926'],
        ['name' => 'Marea', 'url' => '/images/project-covers/gen-marea.webp'],
        ['name' => 'Coral', 'url' => '/images/project-covers/gen-coral.webp'],
        ['name' => 'Noche violeta', 'url' => '/images/project-covers/gen-noche-violeta.webp'],
        ['name' => 'Bosque niebla', 'url' => '/images/project-covers/gen-bosque-niebla.webp'],
        ['name' => 'Vidrio azul', 'url' => '/images/project-covers/photo-1618005182384-a83a8bd57fbe.webp', 'legacyId' => 'photo-1618005182384-a83a8bd57fbe'],
        ['name' => 'Luz líquida', 'url' => '/images/project-covers/photo-1557672172-298e090bd0f1.webp', 'legacyId' => 'photo-1557672172-298e090bd0f1'],
        ['name' => 'Cromo', 'url' => '/images/project-covers/photo-1604076913837-52ab5629fba9.webp', 'legacyId' => 'photo-1604076913837-52ab5629fba9'],
        ['name' => 'Bruma violeta', 'url' => '/images/project-covers/photo-1579547621706-1a9c79d5c9f1.webp', 'legacyId' => 'photo-1579547621706-1a9c79d5c9f1'],
        ['name' => 'Malla azul', 'url' => '/images/project-covers/photo-1614851099362-9adf73ccebe9.webp', 'legacyId' => 'photo-1614851099362-9adf73ccebe9'],
        ['name' => 'Tinta', 'url' => '/images/project-covers/photo-1574169208507-84376144848b.webp', 'legacyId' => 'photo-1574169208507-84376144848b'],
        ['name' => 'Pulso', 'url' => '/images/project-covers/photo-1620121684840-edffcfc4b878.webp', 'legacyId' => 'photo-1620121684840-edffcfc4b878'],
        ['name' => 'Líneas', 'url' => '/images/project-covers/photo-1541701494587-cb58502866ab.webp', 'legacyId' => 'photo-1541701494587-cb58502866ab'],
        ['name' => 'Escritorio foco', 'url' => '/images/project-covers/photo-1497366754035-f200968a6e72.webp', 'legacyId' => 'photo-1497366754035-f200968a6e72'],
        ['name' => 'Código nocturno', 'url' => '/images/project-covers/photo-1515879218367-8466d910aaa4.webp', 'legacyId' => 'photo-1515879218367-8466d910aaa4'],
        ['name' => 'Tablero creativo', 'url' => '/images/project-covers/photo-1516321318423-f06f85e504b3.webp', 'legacyId' => 'photo-1516321318423-f06f85e504b3'],
        ['name' => 'Oficina cálida', 'url' => '/images/project-covers/photo-1497366811353-6870744d04b2.webp', 'legacyId' => 'photo-1497366811353-6870744d04b2'],
        ['name' => 'Mesa productiva', 'url' => '/images/project-covers/photo-1519389950473-47ba0277781c.webp', 'legacyId' => 'photo-1519389950473-47ba0277781c'],
        ['name' => 'Laptop limpia', 'url' => '/images/project-covers/photo-1498050108023-c5249f4df085.webp', 'legacyId' => 'photo-1498050108023-c5249f4df085'],
        ['name' => 'Estrategia', 'url' => '/images/project-covers/photo-1552664730-d307ca884978.webp', 'legacyId' => 'photo-1552664730-d307ca884978'],
        ['name' => 'Notas y café', 'url' => '/images/project-covers/photo-1517245386807-bb43f82c33c4.webp', 'legacyId' => 'photo-1517245386807-bb43f82c33c4'],
        ['name' => 'Arquitectura', 'url' => '/images/project-covers/photo-1486406146926-c627a92ad1ab.webp', 'legacyId' => 'photo-1486406146926-c627a92ad1ab'],
        ['name' => 'Ciudad azul', 'url' => '/images/project-covers/photo-1494526585095-c41746248156.webp', 'legacyId' => 'photo-1494526585095-c41746248156'],
        ['name' => 'Montañas', 'url' => '/images/project-covers/photo-1500530855697-b586d89ba3ee.webp', 'legacyId' => 'photo-1500530855697-b586d89ba3ee'],
        ['name' => 'Bosque', 'url' => '/images/project-covers/photo-1448375240586-882707db888b.webp', 'legacyId' => 'photo-1448375240586-882707db888b'],
        ['name' => 'Océano', 'url' => '/images/project-covers/photo-1507525428034-b723cf961d3e.webp', 'legacyId' => 'photo-1507525428034-b723cf961d3e'],
        ['name' => 'Arena', 'url' => '/images/project-covers/photo-1500534314209-a25ddb2bd429.webp', 'legacyId' => 'photo-1500534314209-a25ddb2bd429'],
        ['name' => 'Niebla verde', 'url' => '/images/project-covers/photo-1473773508845-188df298d2d1.webp', 'legacyId' => 'photo-1473773508845-188df298d2d1'],
        ['name' => 'Minimal blanco', 'url' => '/images/project-covers/photo-1494438639946-1ebd1d20bf85.webp', 'legacyId' => 'photo-1494438639946-1ebd1d20bf85'],
        ['name' => 'Luces ciudad', 'url' => '/images/project-covers/photo-1519608487953-e999c86e7455.webp', 'legacyId' => 'photo-1519608487953-e999c86e7455'],
        ['name' => 'Textura papel', 'url' => '/images/project-covers/photo-1506784983877-45594efa4cbe.webp', 'legacyId' => 'photo-1506784983877-45594efa4cbe'],
        ['name' => 'Trabajo en equipo', 'url' => '/images/project-covers/photo-1521737604893-d14cc237f11d.webp', 'legacyId' => 'photo-1521737604893-d14cc237f11d'],
    ];

    public static function imageFiles(array $project): array
    {
        return array_values(array_filter($project['files'] ?? [], fn ($file) => !empty($file['id'])
            && isset(self::IMAGE_EXTENSIONS[$file['mime'] ?? ''])));
    }

    public static function documentPreview(string $id): string
    {
        return route('documentos.preview', ['id' => $id], false);
    }
}
