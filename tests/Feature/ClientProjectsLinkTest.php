<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientProjectsLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('clientes.json', json_encode([
            ['id' => 'client-1', 'empresa' => 'Dproperty', 'proyectos' => 0],
            ['id' => 'client-2', 'empresa' => 'Otra empresa', 'proyectos' => 0],
        ]));
        Storage::disk('local')->put('proyectos.json', json_encode([
            ['id' => 'project-1', 'cliente_id' => 'client-1', 'titulo' => 'Sitio web'],
            ['id' => 'project-2', 'cliente' => ' dproperty ', 'titulo' => 'Proyecto anterior'],
            ['id' => 'project-3', 'cliente_id' => 'client-2', 'titulo' => 'Proyecto ajeno'],
            ['id' => 'project-4', 'cliente_id' => 'client-1', 'titulo' => 'Proyecto archivado', 'archived' => true],
        ]));
        Storage::disk('local')->put('facturas.json', json_encode([['id' => 'invoice-1', 'cliente' => 'Cliente ajeno', 'total' => 0]]));
        Storage::disk('local')->put('gastos.json', json_encode([['id' => 'expense-1', 'cliente_id' => 'client-2', 'monto' => 0]]));
        Storage::disk('local')->put('settings.json', json_encode([['id' => 'settings', 'base_currency' => 'USD']]));
        Storage::disk('local')->put('timeline.json', json_encode([['id' => 'timeline-1', 'cliente_id' => 'client-2', 'tipo' => 'nota']]));
    }

    public function test_client_list_counts_current_and_legacy_projects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('clientes.index'));

        $response->assertOk();
        $clients = collect($response->viewData('clientes'))->keyBy('id');
        $this->assertSame(2, $clients['client-1']['proyectos']);
        $this->assertSame(1, $clients['client-2']['proyectos']);
    }

    public function test_client_detail_shows_only_its_active_projects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('clientes.show', 'client-1'));

        $response->assertOk()->assertSee('Sitio web')->assertSee('Proyecto anterior');
        $response->assertDontSee('Proyecto ajeno')->assertDontSee('Proyecto archivado');
    }
}
