<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileStore
{
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
        $backupPath = $backupDir . '/' . $base . '_' . date('Ymd_His') . '.json';
        if (!is_file($backupPath)) {
            File::copy($path, $backupPath);
        }
    }
}
