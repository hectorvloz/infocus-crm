<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStore
{
    public function __construct(private string $file)
    {
        if (!Storage::exists($this->file)) {
            Storage::put($this->file, json_encode([]));
        }
    }

    public function all(): array
    {
        return json_decode(Storage::get($this->file), true) ?: [];
    }

    public function find(string $id): ?array
    {
        $all = $this->all();
        return collect($all)->firstWhere('id', $id);
    }

    public function create(array $data): array
    {
        $all = $this->all();
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
        Storage::put($this->file, json_encode($all));
        return $updated;
    }

    public function delete(string $id): void
    {
        $all = array_values(array_filter($this->all(), fn ($i) => $i['id'] !== $id));
        Storage::put($this->file, json_encode($all));
    }

    public function save(array $data): void
    {
        Storage::put($this->file, json_encode($data));
    }
}

