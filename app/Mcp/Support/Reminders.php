<?php

namespace App\Mcp\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Recordatorios del encabezado ("campanita") del usuario actual, con el mismo formato que guarda la app. */
class Reminders
{
    public const FILE = 'header_reminders.json';

    public static function key(): string
    {
        $user = Auth::user();

        return 'user:' . Str::slug(Str::lower(trim((string) ($user->email ?: $user->id))), '-');
    }

    public static function row(): ?array
    {
        return (new FileStore(self::FILE))->find(self::key());
    }

    public static function payload(): array
    {
        $payload = self::row()['payload'] ?? null;

        return is_array($payload) ? $payload : ['categories' => [], 'sections' => [], 'items' => []];
    }
}
