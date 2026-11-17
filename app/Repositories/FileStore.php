<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileStore
{
    private const BACKUP_MIN_INTERVAL = 1800;
    private const BACKUP_KEEP = 20;
    private const BACKUP_MAX_DAYS = 7;

    public function __construct(private string $file)
    {
        if (!Storage::exists($this->file)) {
            $recovered = $this->recoverFromCandidateFiles();
            Storage::put($this->file, json_encode($recovered));
        }
    }

    public function all(): array
    {
        $data = json_decode(Storage::get($this->file), true);
        $data = is_array($data) ? $data : [];

        if ($this->countRecords($data) === 0) {
            $recovered = $this->recoverFromCandidateFiles();
            if ($this->countRecords($recovered) > 0) {
                Storage::put($this->file, json_encode($recovered));
                return $recovered;
            }
        }

        return $data;
    }

    public function find(string $id): ?array
    {
        $all = $this->all();
        return collect($all)->firstWhere('id', $id);
    }

    public function create(array $data): array
    {
        $all = $this->all();
        $this->backupCurrentFile();
        $data['id'] = $data['id'] ?? (string) Str::ulid();
        $data['created_at'] = $data['created_at'] ?? now()->toISOString();
        $data['updated_at'] = now()->toISOString();
        $all[] = $data;
        Storage::put($this->file, json_encode($all));
        return $data;
    }

    public function update(string $id, array $data): ?array
    {
        $all = $this->all();
        $updated = null;
        foreach ($all as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $data);
                $item['updated_at'] = now()->toISOString();
                $updated = $item;
                break;
            }
        }
        $this->backupCurrentFile();
        Storage::put($this->file, json_encode($all));
        return $updated;
    }

    public function delete(string $id): void
    {
        $all = array_values(array_filter($this->all(), fn ($i) => $i['id'] !== $id));
        $this->backupCurrentFile();
        Storage::put($this->file, json_encode($all));
    }

    public function save(array $data): void
    {
        $this->backupCurrentFile();
        Storage::put($this->file, json_encode($data));
    }

    private function recoverFromCandidateFiles(): array
    {
        $best = [];

        foreach ($this->candidatePaths() as $path) {
            if (!is_file($path)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($path), true);
            if (!is_array($decoded)) {
                continue;
            }

            if ($this->countRecords($decoded) > $this->countRecords($best)) {
                $best = $decoded;
            }
        }

        return $best;
    }

    private function candidatePaths(): array
    {
        return array_values(array_unique(array_filter([
            $this->safeStoragePath(),
            storage_path('app/private/' . $this->file),
            storage_path('app/' . $this->file),
            storage_path('app/public/' . $this->file),
            storage_path('app/public/app/private/' . $this->file),
        ])));
    }

    private function safeStoragePath(): ?string
    {
        try {
            return Storage::path($this->file);
        } catch (\Throwable) {
            return null;
        }
    }

    private function countRecords(array $data): int
    {
        return count($data);
    }

    private function backupCurrentFile(): void
    {
        $path = $this->safeStoragePath();
        if (!$path || !is_file($path) || filesize($path) <= 2) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded) || $this->countRecords($decoded) === 0) {
            return;
        }

        $backupDir = storage_path('app/private/.backups/' . str_replace(['/', '\\'], '_', dirname($this->file)));
        File::ensureDirectoryExists($backupDir);

        $base = basename($this->file, '.json');

        // Primero limpia: si el disco se llenó con copias viejas, esto libera espacio antes de escribir.
        $existing = $this->pruneBackups($this->backupsFor($backupDir, $base));

        // Una copia cada 30 minutos por archivo basta como red de seguridad.
        $latest = end($existing);
        if ($latest !== false && (time() - (int) @filemtime($latest)) < self::BACKUP_MIN_INTERVAL) {
            return;
        }

        $backupPath = $backupDir . '/' . $base . '_' . date('Ymd_His') . '.json';
        if (!is_file($backupPath)) {
            @copy($path, $backupPath);
        }
    }

    /** @return list<string> copias de este archivo, de la más vieja a la más nueva */
    private function backupsFor(string $backupDir, string $base): array
    {
        $pattern = '/^' . preg_quote($base, '/') . '_\d{8}_\d{6}\.json$/';
        $files = array_values(array_filter(
            glob($backupDir . '/' . $base . '_*.json') ?: [],
            fn (string $f) => preg_match($pattern, basename($f)) === 1
        ));
        sort($files);

        return $files;
    }

    /**
     * Conserva las últimas BACKUP_KEEP copias que tengan menos de BACKUP_MAX_DAYS días
     * y borra el resto. Devuelve las que quedan.
     *
     * @param  list<string>  $files
     * @return list<string>
     */
    private function pruneBackups(array $files): array
    {
        $limit = time() - self::BACKUP_MAX_DAYS * 86400;
        $total = count($files);
        $kept = [];
        foreach ($files as $i => $file) {
            if ($total - $i <= self::BACKUP_KEEP && (int) @filemtime($file) >= $limit) {
                $kept[] = $file;
                continue;
            }
            @unlink($file);
        }

        return $kept;
    }
}
