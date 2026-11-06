<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['clientes.json', 'proyectos.json', 'facturas.json', 'users.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Storage::disk('local')->put('settings.json', json_encode([['id' => 'settings', 'base_currency' => 'COP']]));
    }

    public function test_create_form_has_country_selector_with_colombia_selected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $html = $this->actingAs($admin)->get(route('clientes.create'))->assertOk()->getContent();

        $this->assertStringContainsString('<select name="pais"', $html);
        $this->assertMatchesRegularExpression('/<option value="Colombia"\s+selected/', $html);
        $this->assertStringNotContainsString('name="website" type="url"', $html);
    }

    public function test_website_without_protocol_is_accepted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('clientes.store'), [
            'empresa' => 'Asasas', 'estado' => 'Activo', 'website' => 'asasas.com', 'pais' => 'México',
        ])->assertSessionHasNoErrors();

        $client = collect((new FileStore('clientes.json'))->all())->firstWhere('empresa', 'Asasas');
        $this->assertSame('https://asasas.com', $client['website']);
        $this->assertSame('México', $client['pais']);
    }
}
