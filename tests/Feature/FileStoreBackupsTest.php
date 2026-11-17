<?php

namespace Tests\Feature;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FileStoreBackupsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        $this->dir = storage_path('app/private/.backups/.');
        File::delete(glob($this->dir . '/respaldo_prueba_*.json') ?: []);
    }

    protected function tearDown(): void
    {
        File::delete(glob($this->dir . '/respaldo_prueba_*.json') ?: []);
        File::delete([storage_path('app/private/respaldo_prueba.json'), storage_path('app/respaldo_prueba.json')]);
        parent::tearDown();
    }

    public function test_many_writes_make_at_most_one_backup_per_interval(): void
    {
        $store = new FileStore('respaldo_prueba.json');
        $store->create(['id' => 'a', 'nombre' => 'uno']);
        for ($i = 0; $i < 30; $i++) {
            $store->update('a', ['nombre' => 'cambio ' . $i]);
        }

        $this->assertLessThanOrEqual(1, count(glob($this->dir . '/respaldo_prueba_*.json') ?: []));
    }

    public function test_old_and_excess_backups_are_pruned(): void
    {
        File::ensureDirectoryExists($this->dir);
        for ($i = 0; $i < 40; $i++) {
            $file = $this->dir . '/respaldo_prueba_202601' . sprintf('%02d', ($i % 28) + 1) . '_' . sprintf('%06d', $i) . '.json';
            file_put_contents($file, '[{"id":"x"}]');
            touch($file, time() - 30 * 86400); // un mes de antigüedad
        }
        file_put_contents($this->dir . '/respaldo_prueba_extra_20260101_000000.json', '[]');

        $store = new FileStore('respaldo_prueba.json');
        $store->create(['id' => 'a', 'nombre' => 'uno']);
        $store->update('a', ['nombre' => 'dos']);

        $left = glob($this->dir . '/respaldo_prueba_2*.json') ?: [];
        $this->assertCount(1, $left, 'Solo queda la copia nueva; las de hace un mes se borran.');
        $this->assertFileExists($this->dir . '/respaldo_prueba_extra_20260101_000000.json', 'No toca copias de otro archivo.');
        File::delete($this->dir . '/respaldo_prueba_extra_20260101_000000.json');
    }
}
