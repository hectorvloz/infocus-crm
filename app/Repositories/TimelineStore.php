<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TimelineStore
{
    protected string $file = 'timelines.json';

    protected function read(): array
    {
        if (!Storage::exists($this->file)) {
            Storage::put($this->file, json_encode([]));
        }
        return json_decode(Storage::get($this->file), true) ?: [];
    }

    protected function write(array $data): void
    {
        Storage::put($this->file, json_encode($data));
    }

    public function for(string $clienteId): array
    {
        return collect($this->read())
            ->where('cliente_id', $clienteId)
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function add(string $clienteId, string $tipo, array $payload): array
    {
        $all = $this->read();
        $event = [
            'id' => (string) Str::ulid(),
            'cliente_id' => $clienteId,
            'tipo' => $tipo,
            'payload' => $payload,
            'created_at' => now()->toISOString(),
        ];
        $all[] = $event;
        $this->write($all);
        return $event;
    }
}

